<?php
namespace App\Filament\Resources\MovimientoResource\RelationManagers;

use App\Models\Product;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MovimientoProductosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientoProductos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('producto_id')
                    ->options(Product::all()->pluck('product_name', 'id')->toArray())
                    ->preload()
                    ->searchable()
                    ->label('Producto')
                    ->required()
                    ->reactive() // Hacer que el campo producto sea reactivo
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $movimiento = $this->ownerRecord;
                        $wharehouseId = $movimiento->bodega_origen_id;
                        $productId = $get('producto_id');
                        $tipoMovimiento = $movimiento->tipo->value;

                        // Solo obtener el stock si es tipo "salida" o "traslado"
                        if ($tipoMovimiento === 'salida' || $tipoMovimiento === 'traslado') {
                            $stock = Stock::where('product_id', $productId)
                                ->where('wharehouse_id', $wharehouseId)
                                ->first();

                            $set('stock_disponible', $stock ? $stock->quantity : 'Sin stock');
                        }

                        // Asignar el wharehouse_id directamente en el formulario
                        $set('wharehouse_id', $wharehouseId);


                        // Obtener el producto y su unidad de medida
                        $product = Product::find($productId);

                        // Si el producto tiene una unidad de medida, establecerla
                        if ($product) {
                            $set('unidad_medida', $product->unit_measure);
                        }
                    }),
                Forms\Components\TextInput::make('stock_disponible')
                    ->label('Stock Disponible')
                    ->disabled() // Solo lectura
                    ->default('Sin stock'),
                Forms\Components\TextInput::make('cantidad')
                    ->required()
                    ->label('Cantidad')
                    ->numeric()
                    ->reactive(),
                // Mostrar el precio solo cuando el movimiento sea de tipo "entrada"
                Forms\Components\TextInput::make('precio_compra')
                    ->label('Precio')
                    ->numeric()
                    ->visible(fn (callable $get) => $this->ownerRecord->tipo->value === 'entrada') // Mostrar solo si es "entrada"
                    ->required(fn (callable $get) => $this->ownerRecord->tipo->value === 'entrada'), // Solo requerido para "entrada"
                // Unidad de medida, cargada dinámicamente desde el producto
                Forms\Components\TextInput::make('unidad_medida')
                    ->label('Unidad de Medida')
                    ->required()
                    ->disabled() // Deshabilitado porque se carga automáticamente desde el producto
                    ->default('Sin unidad'), // Valor por defecto si no se selecciona un producto
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('producto.product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio_compra')
                    ->label('Precio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unidad_medida')
                    ->label('Unidad de Medida'),
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
                    ExportBulkAction::make(),
                ]),
            ]);
    }
}
