<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

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
                        $order = $this->getOwnerRecord(); // Obtener el registro principal relacionado (orden)
                        $families = $order->family ?? []; // Asegúrate de que 'family' es un array

                        if (!empty($families)) {
                            return Product::whereIn('family', $families)
                                ->pluck('product_name', 'id')
                                ->toArray();
                        }

                        return Product::all()->pluck('product_name', 'id')->toArray();
                    })
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $productId = $get('product_id');
                        if ($productId) {
                            $product = Product::find($productId);
                            if ($product) {
                                // Actualizar los valores de waiting_time y reentry basados en el producto seleccionado
                                $set('waiting_time', $product->waiting_time);
                                $set('reentry', $product->reentry);
                                $set('active_ingredients', $product->active_ingredients);

                                // Obtener el stock de producto en la bodega
                                $order = $this->getOwnerRecord();
                                $warehouseId = $order->warehouse_id ?? null;

                                if ($warehouseId) {
                                    $stock = \App\Models\Stock::where('product_id', $productId)
                                        ->where('warehouse_id', $warehouseId)
                                        ->value('quantity') ?? 0;

                                    $set('ProductStock', $stock);
                                } else {
                                    // Si no hay bodega asociada, establecer el stock a null o un mensaje
                                    $set('ProductStock', 'No hay bodega asociada a la orden');
                                }
                            }
                        } else {
                            // Si no hay un producto seleccionado, establecer los valores a null o un valor predeterminado
                            $set('waiting_time', null);
                            $set('reentry', null);
                            $set('ProductStock', null);
                        }

                        // También recalculamos el uso estimado en caso de que el producto haya cambiado
                        $this->recalculateEstimatedUsage($get, $set);
                    }),
                Forms\Components\TextInput::make('active_ingredients')
                    ->label('Ingredientes Activos')
                    ->readOnly(),
                Forms\Components\TextInput::make('dosis')
                    ->required()
                    ->label('Dosis')
                    ->suffix('l/100l')
                    ->numeric()
                    ->reactive()
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
                    })
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $productId = $get('product_id');
                        $dosis = $get('dosis') ?? 0;
                        if ($productId && $dosis > 0) {
                            $order = $this->getOwnerRecord();
                            $order->totalArea;
                            $totalArea = $order->totalArea;

                            $wetting = $order->wetting ?? 0;
                            Log::info("Total Area: {$totalArea}, Wetting: {$wetting}");

                            if ($totalArea > 0 && $wetting > 0) {
                                $estimatedUsage = ($totalArea * $wetting * $dosis) / 100;
                                $set('EstimatedProductUsage', round($estimatedUsage, 2));
                            } else {
                                $set('EstimatedProductUsage', 'Datos insuficientes para calcular');
                            }
                        } else {
                            $set('EstimatedProductUsage', null);
                        }
                    }),
                Forms\Components\TextInput::make('waiting_time')
                    ->label('Carencia')
                    ->numeric(),
                Forms\Components\TextInput::make('reentry')
                    ->label('Reingreso')
                    ->numeric(),
                Forms\Components\TextInput::make('EstimatedProductUsage')
                    ->label('Cantidad estimada de uso de producto')
                    ->readonly()
                    ->reactive()
                    ->suffix('kg/lt')
                    ->numeric()
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
                Forms\Components\TextInput::make('ProductStock')
                    ->label('Stock de producto')
                    ->readonly()
                    ->suffix('kg/lt')
                    ->reactive()
                    ->numeric(),
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
                ->suffix('      l/100l')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: '.', decimalSeparator: ',')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waiting_time')
                    ->label('Carencia')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ',')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reentry')
                    ->label('Reingreso')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar producto')
                    ->icon('heroicon-o-plus')
                    ->color('primary'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([

            ]);
    }
    private function recalculateEstimatedUsage(callable $get, callable $set)
    {
        $productId = $get('product_id');
        $dosis = $get('dosis') ?? 0;

        if ($productId && $dosis > 0) {
            $order = $this->getOwnerRecord();
            $order->totalArea;
            $totalArea = $order->totalArea;

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
