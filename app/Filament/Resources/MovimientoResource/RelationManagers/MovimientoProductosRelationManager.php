<?php

namespace App\Filament\Resources\MovimientoResource\RelationManagers;

use App\Models\Product;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Validation\ValidationException;

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
                    ->live()
                    ->searchable()
                    ->label('Producto')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $movimiento = $this->ownerRecord;

                        // Verificar si el movimiento y el producto existen antes de continuar
                        if ($movimiento && $movimiento->tipo && $get('producto_id')) {
                            $warehouseId = $movimiento->bodega_origen_id;
                            $productId = $get('producto_id');
                            $tipoMovimiento = $movimiento->tipo->value;

                            // Solo obtener el stock si es tipo "salida" o "traslado"
                            if ($tipoMovimiento === 'salida' || $tipoMovimiento === 'traslado') {
                                $stock = Stock::where('product_id', $productId)
                                    ->where('warehouse_id', $warehouseId)
                                    ->first();

                                $stockDisponible = $stock ? $stock->quantity : 'Sin stock';
                                $set('stock_disponible', $stockDisponible);

                                // Log para depuración
                                Log::info('Stock disponible: ' . $stockDisponible);
                            } else {
                                $set('stock_disponible', 'N/A');
                            }

                            // Asignar el warehouse_id directamente en el formulario
                            $set('warehouse_id', $warehouseId);

                            // Obtener el producto y su unidad de medida
                            $product = Product::find($productId);

                            if ($product) {
                                $set('unidad_medida', $product->unit_measure);

                                $set('precio_compra', $product->price);
                            } else {
                                $set('unidad_medida', 'Sin unidad');
                                $set('precio_compra', null);
                            }
                        } else {
                            // Manejar el caso donde el movimiento o su tipo es null
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
                    ->numeric()
                    ->live()
                    ->reactive(),
                Forms\Components\TextInput::make('cantidad')
                    ->required()
                    ->visible(fn (callable $get) => $this->ownerRecord && $this->ownerRecord->tipo && $this->ownerRecord->tipo->value !== 'entrada')
                    ->label('Cantidad')
                    ->numeric()
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
                    ->visible(fn (callable $get) => $this->ownerRecord && $this->ownerRecord->tipo->value === 'entrada'),

                Forms\Components\DatePicker::make('expiration_date')
                    ->label('Fecha de Vencimiento')
                    ->date()
                    ->visible(fn (callable $get) => $this->ownerRecord && $this->ownerRecord->tipo->value === 'entrada'),

                Forms\Components\Hidden::make('precio_compra'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
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
                    ->label('Unidad de Medida'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
