<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Provider;
use Filament\Forms\Form;
use App\Enums\StatusType;
use Filament\Tables\Table;
use App\Models\PurchaseOrder;
use App\Models\MovimientoProducto;
use App\Enums\MovementType;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use App\Models\PurchaseOrderDetail;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers\PurchaseinsRelationManager;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers\PurchaseOrderDetailsRelationManager;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'fas-sheet-plastic';
    protected static ?string $navigationGroup = 'Bodega';

    protected static ?string $slug = 'oc';
    protected static ?string $pluralModelLabel = 'Ordenes de compra';
    protected static ?string $modelLabel = 'Orden de Compra';
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        $isCompleted = fn ($record) => $record && $record->status === StatusType::COMPLETO;

        return $form
            ->schema([
                Forms\Components\TextInput::make('number')
                    ->label('Número de orden')
                    ->integer()
                    ->rules(function (Forms\Get $get) {
                        return Rule::unique('purchase_orders', 'number')
                            ->ignore($get('id'));
                    })
                    ->required()
                    ->disabled($isCompleted),

                Forms\Components\Select::make('provider_id')
                    ->label('Proveedor')
                    ->preload()
                    ->options(
                        Provider::all()->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable()
                    ->disabled($isCompleted),

                Forms\Components\DatePicker::make('date')
                    ->label('Fecha creación')
                    ->default(now())
                    ->required()
                    ->disabled($isCompleted),

                Forms\Components\ToggleButtons::make('status')
                    ->label('Estado')
                    ->inline()
                    ->grouped()
                    ->hidden(fn($record) => $record == null)
                    ->options(StatusType::class)
                    ->disableOptionWhen(fn($record) => $record !== null)
                    ->disabled($isCompleted),

                Forms\Components\Textarea::make('observation')
                    ->label('Observación')
                    ->nullable()
                    ->disabled($isCompleted),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('N° Orden')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha creación')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('porcentaje_recepcion') // Nueva columna para % de recepción
                    ->label('% Recepción')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        // Calcular cantidad ordenada total (suma de quantity en detalles)
                        $cantidadOrdenada = $record->PurchaseOrderDetails()->sum('quantity');

                        // Calcular cantidad recibida total (suma de cantidad en movimientos de entrada asociados)
                        $cantidadRecibida = MovimientoProducto::whereHas('movimiento', function (Builder $query) use ($record) {
                            $query->where('purchase_order_id', $record->id)
                                  ->where('tipo', MovementType::ENTRADA);
                        })->sum('cantidad');

                        // Evitar división por cero
                        return $cantidadOrdenada > 0 ? number_format(($cantidadRecibida / $cantidadOrdenada) * 100, 2) . '%' : '0%';
                    }),

                Tables\Columns\TextColumn::make('observation')
                    ->label('Observación')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status !== StatusType::COMPLETO), // Oculta la acción de edición si está completo
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar')
                        ->color('primary'),
                ]),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            PurchaseOrderDetailsRelationManager::class,
            PurchaseinsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}