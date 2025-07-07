<?php

namespace App\Filament\Resources\MovimientoResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Stock;
use App\Models\Package;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Google\Service\Drive\Label;
use App\Models\PurchaseOrderDetail;
use Illuminate\Support\Facades\Log;
use Filament\Resources\RelationManagers\RelationManager;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MovimientoProductosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientoProductos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('producto_id')
                ->options(function (callable $get) {
                    $movimiento = $this->ownerRecord;

                    if ($movimiento) {
                        // Check if the movement type is 'entrada'
                        if ($movimiento->tipo->value === 'entrada') {
                            // Si no hay orden de compra seleccionada, mostrar todos los productos
                            if (empty($movimiento->orden_compra)) {
                                return Product::pluck('product_name', 'id');
                            }

                            // Si hay orden de compra, filtrar productos según PurchaseOrderDetail
                            $purchaseOrderDetail = PurchaseOrderDetail::where('purchase_order_id', $movimiento->orden_compra)->get();
                            $productIds = $purchaseOrderDetail->pluck('product_id');
                            return Product::whereIn('id', $productIds)->pluck('product_name', 'id');
                        } else {
                            $warehouseId = $movimiento->bodega_origen_id;

                            if ($movimiento->order) {
                                // Obtener los IDs de productos de las líneas de la orden
                                $productIdsFromOrder = $movimiento->order->orderLines()->pluck('product_id');

                                // Filtrar productos que están en la orden y tienen stock > 0 en la bodega
                                $productsWithStock = Stock::where('warehouse_id', $warehouseId)
                                    ->where('quantity', '>', 0)
                                    ->whereIn('product_id', $productIdsFromOrder)
                                    ->pluck('product_id');

                                // Obtener los productos filtrados
                                $products = Product::whereIn('id', $productsWithStock)->pluck('product_name', 'id');

                                return $products;
                            } else {
                                // Obtener productos que tienen stock > 0 en la bodega
                                $productsWithStock = Stock::where('warehouse_id', $warehouseId)
                                    ->where('quantity', '>', 0)
                                    ->pluck('product_id');

                                $products = Product::whereIn('id', $productsWithStock)->pluck('product_name', 'id');

                                return $products;
                            }
                        }
                    }
                    // Si no hay movimiento, retornar un array vacío
                    return [];
                })
                ->preload()
                ->live()
                ->searchable()
                ->label('Producto')
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $get, callable $set) {
                    $movimiento = $this->ownerRecord;

                    if ($movimiento && $get('producto_id')) {
                        $warehouseId = $movimiento->bodega_origen_id;
                        $productId = $get('producto_id');

                        $stock = Stock::where('product_id', $productId)
                            ->where('warehouse_id', $warehouseId)
                            ->first();

                        $stockDisponible = $stock ? $stock->quantity : 0;
                        $set('stock_disponible', $stockDisponible);

                        // Obtener el producto y su unidad de medida
                        $product = Product::find($productId);

                        if ($product && $product->requiresBatchControl()) {
                            $set('requires_batch_control', true);
                        } else {
                            $set('requires_batch_control', false);
                        }

                        if ($product) {
                            $set('unidad_medida', $product->unit_measure);
                            $set('precio_compra', $product->price);
                        } else {
                            $set('unidad_medida', 'Sin unidad');
                            $set('precio_compra', null);
                        }
                    } else {
                        $set('stock_disponible', 'Sin movimiento o sin producto seleccionado');
                        $set('warehouse_id', null);
                    }
                }),

                Forms\Components\TextInput::make('stock_disponible')
                    ->label('Stock Disponible')
                    ->disabled()
                    ->numeric()
                    ->default(function (Get $get) {
                        return $get('stock_disponible');
                    })
                    ->visible(fn (callable $get) => $this->ownerRecord && $this->ownerRecord->tipo && $this->ownerRecord->tipo->value !== 'entrada')
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        Log::info('Stock disponible: ' . $get('stock_disponible'));
                    }),

                Forms\Components\TextInput::make('cantidad')
                    ->required()
                    ->visible(fn (callable $get) => $this->ownerRecord && $this->ownerRecord->tipo && $this->ownerRecord->tipo->value == 'entrada')
                    ->label('Cantidad')
                    ->numeric(2)
                    ->live()
                    ->reactive(),
                Forms\Components\TextInput::make('cantidad')
                    ->required()
                    ->visible(fn (callable $get) => $this->ownerRecord && $this->ownerRecord->tipo && $this->ownerRecord->tipo->value !== 'entrada')
                    ->label('Cantidad')
                    ->numeric(2)
                    ->live()
                    ->lte('stock_disponible')
                    ->validationMessages([
                        'lte' => 'La cantidad debe ser menor o igual al stock disponible.',
                    ])
                    ->reactive(),

                Forms\Components\TextInput::make('unidad_medida')
                    ->label('Unidad de Medida')
                    ->required()
                    ->disabled()
                    ->default('Sin unidad'),
                
                Forms\Components\TextInput::make('lot_number')
                    ->label('Número de Lote')
                    ->string()
                    ->visible(fn (callable $get) => $get('requires_batch_control') === true && $this->ownerRecord->tipo->value == 'entrada')
                    ->required(fn (callable $get) => $get('requires_batch_control') === true && $this->ownerRecord->tipo->value == 'entrada'),

                Forms\Components\DatePicker::make('expiration_date')
                    ->label('Fecha de Vencimiento')
                    ->date()
                    ->visible(fn (callable $get) => $get('requires_batch_control') === true && $this->ownerRecord->tipo->value == 'entrada')
                    ->required(fn (callable $get) => $get('requires_batch_control') === true && $this->ownerRecord->tipo->value == 'entrada'),

                    /* Forms\Components\Select::make('package_id')
                    ->label('Envase')
                    ->relationship('package', 'name')
                    ->preload()
                    ->visible(fn (callable $get) => $get('requires_batch_control') === true && $this->ownerRecord->tipo->value == 'entrada')
                    ->required(fn (callable $get) => $get('requires_batch_control') === true && $this->ownerRecord->tipo->value == 'entrada')
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $cantidad = $get('cantidad');
                        $package = Package::find($get('package_id'));
                
                        if ($package) {
                            $cantidad_envases_sugerida = ceil($cantidad / $package->capacity);
                            $set('cantidad_envases', $cantidad_envases_sugerida);
                        }
                    }),
                
                Forms\Components\TextInput::make('cantidad_envases')
                    ->label('Cantidad de Envases')
                    ->numeric()
                    ->minValue(1)
                    ->live()
                    ->helperText(fn (callable $get) => 
                        ($get('package_id') && $get('cantidad_envases') < ceil($get('cantidad') / Package::find($get('package_id'))->capacity))
                        ? '⚠️ La cantidad de envases es menor a la recomendada.'
                        : 'Cantidad calculada automáticamente pero modificable por el usuario.'
                    )
                    ->visible(fn (callable $get) => $get('requires_batch_control') === true && $this->ownerRecord->tipo->value == 'entrada')
                    ->required(fn (callable $get) => $get('requires_batch_control') === true && $this->ownerRecord->tipo->value == 'entrada'),

                Forms\Components\Hidden::make('precio_compra'),
                */
            ]); 
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric(thousandsSeparator:'.', decimalPlaces: 1)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unidad_medida')
                    ->label('Unidad de Medida')
                    ->searchable()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('package.name')
                    ->label('Envase')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(), */

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar producto')
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    ExportBulkAction::make(),
                ]),
            ]);
    }
}
