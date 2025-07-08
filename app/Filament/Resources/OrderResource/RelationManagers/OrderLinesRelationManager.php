<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Actions; // Importar Actions
use Filament\Forms\Components\Actions\Action; // Importar Action

class OrderLinesRelationManager extends RelationManager
{
    protected static ?string $title = 'Productos';
    protected static ?string $modelLabel = 'Producto';
    protected static string $relationship = 'orderLines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->required()
                    ->label('Producto')
                    ->options(function (callable $get) {
                        $order = $this->getOwnerRecord();
                        $families = $order->family ?? [];

                        if (!empty($families)) {
                            return Product::whereIn('family', $families)
                                ->pluck('product_name', 'id')
                                ->toArray();
                        }

                        return Product::all()->pluck('product_name', 'id')->toArray();
                    })
                    ->searchable()
                    ->reactive() // Mantener reactive para actualizar carencia, reingreso, etc.
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $productId = $get('product_id');
                        if ($productId) {
                            $product = Product::find($productId);
                            if ($product) {
                                $set('waiting_time', $product->waiting_time);
                                $set('reentry', $product->reentry);
                                $set('active_ingredients', $product->active_ingredients);

                                $order = $this->getOwnerRecord();
                                $warehouseId = $order->warehouse_id ?? null;

                                if ($warehouseId) {
                                    $stock = \App\Models\Stock::where('product_id', $productId)
                                        ->where('warehouse_id', $warehouseId)
                                        ->value('quantity') ?? 0;
                                    $set('ProductStock', $stock);
                                } else {
                                    $set('ProductStock', 'No hay bodega asociada a la orden');
                                }
                            }
                        } else {
                            $set('waiting_time', null);
                            $set('reentry', null);
                            $set('active_ingredients', null);
                            $set('ProductStock', null);
                        }
                        
                    }),
                Forms\Components\TextInput::make('active_ingredients')
                    ->label('Ingredientes Activos')
                    ->readOnly(),
                Forms\Components\TextInput::make('dosis')
                    ->required()
                    ->label('Dosis')
                    ->suffix('l/100l')
                    ->suffixAction(
                            Actions\Action::make('calculate_usage')
                        ->label('Calcular Uso')
                        ->icon('heroicon-o-calculator')
                        ->button()
                        ->outlined()
                        ->color('info')
                        ->action(function (callable $get, callable $set) {
                            $this->recalculateEstimatedUsage($get, $set);
                        }),
                    )
                    ->numeric(3, ',', '.')
                    ->step(0.001)
                    ->helperText(function (callable $get) {
                        $productId = $get('product_id');
                        if ($productId) {
                            $product = Product::find($productId);
                            $minDosis = $product->dosis_min;
                            $maxDosis = $product->dosis_max;

                            if ($minDosis !== null && $maxDosis !== null) {
                                return "La dosis recomendada está entre {$minDosis} y {$maxDosis} l/100l. Puedes continuar aunque los valores estén fuera de este rango.";
                            }
                        }
                        return null;
                    }),
            
                Forms\Components\TextInput::make('EstimatedProductUsage')
                    ->label('Cantidad estimada de uso de producto')
                    ->readonly()
                    ->suffix('kg/lt')
                    ->numeric(2)
                    ->default(0)
                    ->helperText(function (callable $get) {
                        $estimatedUsage = $get('EstimatedProductUsage');
                        $productStock = $get('ProductStock');

                        if (is_numeric($estimatedUsage) && is_numeric($productStock)) {
                            if ($estimatedUsage > $productStock) {
                                return new \Illuminate\Support\HtmlString('<span style="color: red;">El uso estimado supera el stock disponible.</span>');
                            }
                        }
                        return null;
                    }),
                Forms\Components\TextInput::make('waiting_time')
                    ->label('Carencia')
                    ->required()
                    ->suffix('dias'),
                Forms\Components\TextInput::make('reentry')
                    ->label('Reingreso')
                    ->required()
                    ->suffix('horas'),
                Forms\Components\TextInput::make('ProductStock')
                    ->label('Stock de producto')
                    ->readonly()
                    ->suffix('kg/lt')
                    ->reactive()
                    ->numeric(),
                Forms\Components\Toggle::make('notes')
                    ->default(true)
                    ->label('Verificado'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dosis')
                    ->label('Dosis')
                    ->suffix(' l/100l')
                    ->numeric(decimalPlaces: 3, thousandsSeparator: '.', decimalSeparator: ',')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waiting_time')
                    ->label('Carencia')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ',')
                    ->suffix(' dias')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reentry')
                    ->label('Reingreso')
                    ->searchable()
                    ->suffix(' horas')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar producto')
                    ->icon('heroicon-o-plus')
                    ->visible(fn($record) => $this->getOwnerRecord()->orderApplications()->count() === 0)
                    ->color('primary'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $this->getOwnerRecord()->orderApplications()->count() === 0),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => $this->getOwnerRecord()->orderApplications()->count() === 0),
            ])
            ->bulkActions([
                //
            ]);
    }

    private function recalculateEstimatedUsage(callable $get, callable $set)
    {
        $productId = $get('product_id');
        $dosis = $get('dosis') ?? 0;

        if ($productId && $dosis > 0) {
            $order = $this->getOwnerRecord();
            $totalArea = $order->totalArea ?? 0;
            $wetting = $order->wetting ?? 0;

            Log::info("Total Area: {$totalArea}, Wetting: {$wetting}");

            if ($totalArea > 0 && $wetting > 0) {
                $estimatedUsage = ($totalArea * $wetting * $dosis) / 100;
                Log::info("Estimated Usage: {$estimatedUsage}");
                $set('EstimatedProductUsage', round($estimatedUsage, 2));
            } else {
                Log::warning("Datos insuficientes para calcular el uso estimado.");
                $set('EstimatedProductUsage', 'Datos insuficientes para calcular');
            }
        } else {
            Log::warning("Product ID o dosis no válidos.");
            $set('EstimatedProductUsage', null);
        }
    }
}