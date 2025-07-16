<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\MovementType;
use App\Models\MovimientoProducto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class PurchaseOrderDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'PurchaseOrderDetails';
    protected static ?string $title = 'Detalle de la orden de compra';
    protected static ?string $modelLabel = 'Productos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Producto')
                    ->required()
                    ->options(Product::all()->pluck('product_name', 'id'))
                    ->preload()
                    ->searchable()
                    ->rules('exists:products,id')
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        $product = Product::find($state);
                        $set('price', $product ? $product->price : 0);
                    }),

                Forms\Components\Fieldset::make('Precio Configuración')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Precio USD')
                            ->numeric()
                            ->required()
                            ->disabled(fn (Forms\Get $get) => !$get('edit_price'))
                            ->dehydrated()
                            ->columnSpan(3),

                        Forms\Components\Checkbox::make('edit_price')
                            ->label('Modificar Precio')
                            ->default(false)
                            ->live()
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    ->columns(4),

                Forms\Components\TextInput::make('quantity')   
                    ->label('Cantidad')
                    ->numeric()
                    ->required(),

                Forms\Components\Textarea::make('observation')
                    ->label('Observación')
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio USD')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: '.', decimalSeparator: ',')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric(decimalPlaces: 1, thousandsSeparator: '.', decimalSeparator: ',')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cantidad_recibida')
                    ->label('Recibido')
                    ->numeric(decimalPlaces: 1, thousandsSeparator: '.', decimalSeparator: ',')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if (!$record->purchaseOrder) {
                            return 0;
                        }
                        return MovimientoProducto::whereHas('movimiento', function (Builder $query) use ($record) {
                            $query->where('purchase_order_id', $record->purchaseOrder->id)
                                  ->where('tipo', MovementType::ENTRADA);
                        })->where('producto_id', $record->product_id)
                            ->sum('cantidad');
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: '.', decimalSeparator: ',')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('observation')
                    ->label('Observación')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        if (!$record->purchaseOrder) {
                            return true;
                        }
                        $cantidadRecibida = MovimientoProducto::whereHas('movimiento', function (Builder $query) use ($record) {
                            $query->where('purchase_order_id', $record->purchaseOrder->id)
                                  ->where('tipo', MovementType::ENTRADA);
                        })->where('producto_id', $record->product_id)
                            ->sum('cantidad');
                        return $cantidadRecibida == 0;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        if (!$record->purchaseOrder) {
                            return true;
                        }
                        $cantidadRecibida = MovimientoProducto::whereHas('movimiento', function (Builder $query) use ($record) {
                            $query->where('purchase_order_id', $record->purchaseOrder->id)
                                  ->where('tipo', MovementType::ENTRADA);
                        })->where('producto_id', $record->product_id)
                            ->sum('cantidad');
                        return $cantidadRecibida == 0;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}