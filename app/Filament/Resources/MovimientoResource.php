<?php

namespace App\Filament\Resources;

use App\Enums\MovementType;
use App\Enums\RoleType;
use App\Filament\Resources\MovimientoResource\Pages;
use App\Filament\Resources\MovimientoResource\RelationManagers\MovimientoProductosRelationManager;
use App\Models\Field;
use App\Models\InterTenantTransfer;
use App\Models\Movimiento;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MovimientoResource extends Resource
{
    protected static ?string $model = Movimiento::class;

    protected static ?string $navigationGroup = 'Bodega';

    protected static ?int $navigationSort = 20;
    protected static ?string $navigationLabel = 'Agregar movimiento';

    protected static ?string $navigationIcon = 'eva-swap';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $warehouseIds = $user->warehouses()
            ->select('warehouses.id')
            ->pluck('warehouses.id')
            ->toArray();

        return parent::getEloquentQuery()
            ->where(function ($query) use ($warehouseIds) {
                $query->whereIn('bodega_origen_id', $warehouseIds)
                    ->orWhereIn('bodega_destino_id', $warehouseIds);
            })
            ->withCount('movimientoProductos');
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $esEstanquero = $user->role === RoleType::ESTANQUERO->value;

        return $form
            ->schema([
                TextInput::make('id')
                    ->label('ID')
                    ->readonly(),
                Select::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->disabled(fn($record) => $record !== null)
                    ->options(function () use ($user, $esEstanquero) {
                        $movementTypeOptions = [];
                        $allowedMovementTypes = $esEstanquero
                            ? [MovementType::TRASLADO, MovementType::PREPARACION]
                            : array_filter(MovementType::cases(), function ($type) {
                                return !in_array($type->value, [
                                    MovementType::TRASLADO_ENTRADA->value,
                                    MovementType::TRASLADO_SALIDA->value,
                                ]);
                            });

                        foreach ($allowedMovementTypes as $type) {
                            $label = match ($type) {
                                MovementType::ENTRADA => 'Entrada',
                                MovementType::SALIDA => 'Salida',
                                MovementType::TRASLADO => 'Traslado',
                                MovementType::PREPARACION => 'Preparación',
                                MovementType::TRASLADO_CAMPOS => 'Traslado entre Campos',
                                default => ucfirst($type->value),
                            };
                            $movementTypeOptions[$type->value] = $label;
                        }

                        return $movementTypeOptions;
                    })
                    ->reactive(),

                Select::make('bodega_origen_id')
                    ->label('Origen')
                    ->options(function (callable $get) use ($user) {
                        $tenantId = Filament::getTenant()->id;
                        $tipoMovimiento = $get('tipo');

                        if ($tipoMovimiento === MovementType::ENTRADA->value) {
                            return Warehouse::where('field_id', $tenantId)
                                ->where('is_special', true)
                                ->pluck('name', 'id');
                        }

                        return Warehouse::where('field_id', $tenantId)
                            ->whereIn('id', $user->warehouses()->pluck('warehouses.id'))
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->disabled(fn($record) => $record !== null)
                    ->searchable()
                    ->reactive()
                    ->visible(fn($get) => $get('tipo') !== null),

                Select::make('tenant_destino_id')
                    ->label('Campo Destino')
                    ->options(function () {
                        return Field::where('id', '!=', Filament::getTenant()->id)->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->visible(fn($get) => $get('tipo') === MovementType::TRASLADO_CAMPOS->value),

                Select::make('bodega_destino_id')
                    ->label('Bodega Destino')
                    ->options(function (callable $get) {
                        $tenantDestinoId = $get('tenant_destino_id');
                        $tipoMovimiento = $get('tipo');

                        if ($tipoMovimiento === MovementType::TRASLADO_CAMPOS->value && $tenantDestinoId) {
                            return Warehouse::where('field_id', $tenantDestinoId)
                                ->pluck('name', 'id');
                        }

                        $tenantId = Filament::getTenant()->id;
                        if ($tipoMovimiento === MovementType::ENTRADA->value) {
                            return Warehouse::where('field_id', $tenantId)
                                ->where('is_special', false)
                                ->pluck('name', 'id');
                        }
                        if ($tipoMovimiento === MovementType::SALIDA->value) {
                            return Warehouse::where('field_id', $tenantId)
                                ->where('is_special', true)
                                ->pluck('name', 'id');
                        }
                        if ($tipoMovimiento === MovementType::TRASLADO->value) {
                            return Warehouse::where('field_id', $tenantId)
                                ->where('is_special', false)
                                ->pluck('name', 'id');
                        }
                        return Warehouse::where('field_id', $tenantId)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->different('bodega_origen_id')
                    ->disabled(fn($record) => $record !== null)
                    ->searchable()
                    ->reactive()
                    ->visible(fn($get) => in_array($get('tipo'), [
                        MovementType::ENTRADA->value,
                        MovementType::TRASLADO->value,
                        MovementType::SALIDA->value,
                        MovementType::TRASLADO_CAMPOS->value
                    ])),

                Select::make('order_id')
                    ->label('Orden de Aplicación')
                    ->disabled(fn($record) => $record !== null)
                    ->placeholder('Solo para aplicaciones fitosanitarias')
                    ->options(function () {
                        $tenantId = Filament::getTenant()->id;
                        return Order::where('field_id', $tenantId)
                            ->where('is_completed', false)
                            ->pluck('orderNumber', 'id');
                    })
                    ->searchable()
                    ->native(false)
                    ->nullable()
                    ->visible(fn($get) => $get('tipo') === MovementType::PREPARACION->value)
                    ->reactive(),

                Select::make('orden_compra')
                    ->label('Orden de compra')
                    ->native(false)
                    ->options(function () {
                        return PurchaseOrder::all()->pluck('number', 'id')->toArray();
                    })
                    ->visible(fn($get) => $get('tipo') == MovementType::ENTRADA->value)
                    ->reactive(),

                TextInput::make('nombre_proveedor')
                    ->label('Nombre del proveedor')
                    ->visible(fn($get) => $get('tipo') == MovementType::ENTRADA->value)
                    ->dehydrated(true)
                    ->default(function (callable $get) {
                        $ordenCompraId = $get('orden_compra');
                        if ($ordenCompraId) {
                            $purchaseOrder = PurchaseOrder::with('provider')->find($ordenCompraId);
                            return $purchaseOrder?->provider->name ?? '';
                        }
                        return '';
                    })
                    ->reactive(),

                TextInput::make('guia_despacho')
                    ->label('Guía de despacho')
                    ->numeric()
                    ->visible(fn($get) => in_array($get('tipo'), [MovementType::ENTRADA->value, MovementType::SALIDA->value])),

                TextInput::make('comprobante')
                    ->label('Comprobante')
                    ->visible(fn($get) => $get('tipo') == MovementType::TRASLADO->value)
                    ->numeric(),

                TextInput::make('encargado')
                    ->label('Observaciones')
                    ->visible(fn($get) => in_array($get('tipo'), [MovementType::SALIDA->value, MovementType::TRASLADO_CAMPOS->value])),

                Select::make('user_id')
                    ->label('Usuario')
                    ->options(function () {
                        return User::all()->pluck('name', 'id')->toArray();
                    })
                    ->default(fn() => Auth::id())
                    ->disabled()
                    ->reactive(),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            MovementType::ENTRADA => 'entrada',
                            MovementType::SALIDA => 'salida',
                            MovementType::TRASLADO => 'traslado',
                            MovementType::PREPARACION => 'preparación',
                            MovementType::TRASLADO_CAMPOS => 'traslado-campos',
                            default => 'Desconocido',
                        };
                    })
                    ->colors([
                        'entrada' => 'success',
                        'salida' => 'danger',
                        'traslado' => 'warning',
                        'preparacion' => 'danger',
                        'traslado-campos' => 'info',
                    ]),
                Tables\Columns\TextColumn::make('bodega_origen.name')
                    ->label('Origen')
                    ->placeholder('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega_destino.name')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('movimiento_productos_count')
                    ->label('Productos')
                    ->badge()
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('comprobante')
                    ->label('Comprobante')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('encargado')
                    ->label('Observaciones')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Creador')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('interTenantTransfer.id')
                    ->label('Transferencia Inter-Tenant')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'traslado' => 'Traslado',
                        'preparacion' => 'Preparación',
                        'traslado-campos' => 'Traslado entre Campos',
                    ]),
                TernaryFilter::make('is_completed')
                    ->label('Estado')
                    ->default(false)
                    ->trueLabel('Completados')
                    ->falseLabel('Pendientes'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->hidden(fn(Movimiento $record) => $record->is_completed),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn(Movimiento $record) => $record->movimiento_productos_count === 0),
                    Action::make('complete')
                        ->label('Cerrar')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Movimiento $record) {
                            $record->is_completed = true;
                            $record->save();
                            Log::info("Movimiento ID: {$record->id} ha sido completado por el usuario ID: " . Auth::id());
                        })
                        ->authorize('complete') // Usar la política
                        ->hidden(fn(Movimiento $record) => $record->is_completed),
                    Action::make('despachar')
                        ->label('Despachar')
                        ->color('primary')
                        ->icon('heroicon-o-truck')
                        ->requiresConfirmation()
                        ->action(function (Movimiento $record, StockService $stockService) {
                            if ($record->tipo !== MovementType::TRASLADO_CAMPOS->value) {
                                throw new \Exception('Solo los movimientos de tipo Traslado entre Campos pueden ser despachados.');
                            }
                            if ($record->is_completed) {
                                throw new \Exception('El movimiento ya está completado.');
                            }
                            if ($record->movimiento_productos_count === 0) {
                                throw new \Exception('El movimiento debe tener productos asociados.');
                            }
                            if (!$record->tenantDestino) {
                                throw new \Exception('El campo destino no es válido.');
                            }

                            DB::transaction(function () use ($record, $stockService) {
                                $record->is_completed = true;
                                $record->save();

                                $interTenantTransfer = InterTenantTransfer::create([
                                    'tenant_origen_id' => $record->field_id,
                                    'tenant_destino_id' => $record->tenant_destino_id,
                                    'bodega_origen_id' => $record->bodega_origen_id,
                                    'bodega_destino_id' => $record->bodega_destino_id,
                                    'movimiento_origen_id' => $record->id,
                                    'estado' => 'pendiente',
                                    'user_id' => Auth::id(),
                                ]);

                                $record->movimientoProductos()->update([
                                    'inter_tenant_transfer_id' => $interTenantTransfer->id,
                                ]);

                                Log::info("Movimiento ID: {$record->id} despachado. InterTenantTransfer ID: {$interTenantTransfer->id} creado.");
                            });
                        })
                        ->hidden(fn(Movimiento $record) => $record->is_completed),
                ])->button()->size(ActionSize::Small)
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MovimientoProductosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovimientos::route('/'),
            'create' => Pages\CreateMovimiento::route('/create'),
            'edit' => Pages\EditMovimiento::route('/{record}/edit'),
            'view' => Pages\ViewMovimiento::route('/{record}')
        ];
    }
}