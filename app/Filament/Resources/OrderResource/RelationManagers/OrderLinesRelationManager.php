<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                            }
                        } else {
                            // Si no hay un producto seleccionado, establecer los valores a null o un valor predeterminado
                            $set('waiting_time', null);
                            $set('reentry', null);
                        }
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
                        if ($productId) {
                            $product = Product::find($productId);
                            $dosis = $get('dosis');
                            if ($product && $dosis) {
                                $set('dosis', $dosis);
                            }
                        }
                    }),
                Forms\Components\Textarea::make('reasons')
                    ->label('Razón'),
                Forms\Components\TextInput::make('waiting_time')
                    ->label('Carencia')
                    ->numeric(),
                Forms\Components\TextInput::make('reentry')
                    ->label('Reingreso')
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
                Tables\Columns\TextColumn::make('reasons')
                    ->label('Razón')
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
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
