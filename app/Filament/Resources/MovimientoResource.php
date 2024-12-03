<?php

namespace App\Filament\Resources;

use App\Enums\MovementType;
use App\Enums\RoleType;
use App\Filament\Resources\MovimientoResource\Pages;
use App\Filament\Resources\MovimientoResource\RelationManagers\MovimientoProductosRelationManager;
use App\Models\Movimiento;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MovimientoResource extends Resource
{
    protected static ?string $model = Movimiento::class;

    protected static ?string $navigationGroup = 'Bodega';

    protected static ?int $navigationSort = 20;
    protected static ?string $navigationLabel = 'Agregar movimiento';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        // Determinar si el usuario es estanquero
        $esEstanquero = $user->role === RoleType::ESTANQUERO->value;

        return $form
            ->schema([
                // Mover el campo 'tipo' al inicio
                Select::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->searchable()
                    ->required()
                    ->disabled(fn($record) => $record !== null)
                    ->options(function () use ($user, $esEstanquero) {
                        $movementTypeOptions = [];

                        // Definir los tipos de movimiento permitidos según el rol
                        $allowedMovementTypes = $esEstanquero
                            ? [MovementType::TRASLADO, MovementType::PREPARACION]
                            : MovementType::cases();

                        foreach ($allowedMovementTypes as $type) {
                            // Asignar etiquetas más amigables si es necesario
                            $label = match ($type) {
                                MovementType::ENTRADA => 'Entrada',
                                MovementType::SALIDA => 'Salida',
                                MovementType::TRASLADO => 'Traslado',
                                MovementType::PREPARACION => 'Preparación',
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

                        // Si el tipo es 'Entrada', mostrar bodegas con 'is_special == true'
                        if ($tipoMovimiento === MovementType::ENTRADA->value) {
                            return Warehouse::where('field_id', $tenantId)
                                ->where('is_special', true)
                                ->pluck('name', 'id');
                        }

                        // Para otros tipos, mostrar las bodegas asignadas al usuario
                        return Warehouse::where('field_id', $tenantId)
                            ->whereIn('id', $user->warehouses()->pluck('warehouses.id'))
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->disabled(fn($record) => $record !== null)
                    ->searchable()
                    ->reactive()
                    ->visible(fn($get) => $get('tipo') !== null), // Mostrar solo si 'tipo' ha sido seleccionado

                Select::make('bodega_destino_id')
                    ->label('Destino')
                    ->disabled(fn($record) => $record !== null)
                    ->options(function (callable $get) {
                        $tenantId = Filament::getTenant()->id;
                        $tipoMovimiento = $get('tipo');

                        // Si el tipo es 'Entrada', mostrar bodegas con 'is_special == false'
                        if ($tipoMovimiento === MovementType::ENTRADA->value) {
                            return Warehouse::where('field_id', $tenantId)
                                ->where('is_special', false)
                                ->pluck('name', 'id');
                        }

                        // Para otros tipos, mostrar todas las bodegas del tenant
                        return Warehouse::where('field_id', $tenantId)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->visible(fn($get) => in_array($get('tipo'), [MovementType::ENTRADA->value, MovementType::TRASLADO->value]))
                    ->searchable(),

                Select::make('order_id')
                    ->label('Orden de Aplicación')
                    ->disabled(fn($record) => $record !== null)
                    ->placeholder('Solo para aplicaciones fitosanitarias')
                    ->options(function () {
                        $tenantId = Filament::getTenant()->id;
                        // Filtrar solo órdenes que no están completadas
                        return Order::where('field_id', $tenantId)
                            ->where('is_completed', false) // Asegúrate de que este campo exista
                            ->pluck('orderNumber', 'id');
                    })
                    ->searchable()
                    ->nullable()
                    ->visible(fn ($get) => $get('tipo') === MovementType::PREPARACION->value)
                    ->reactive(),

                TextInput::make('orden_compra')
                    ->label('Orden de compra')
                    ->numeric()
                    ->visible(fn ($get) => $get('tipo') == MovementType::ENTRADA->value),

                TextInput::make('nombre_proveedor')
                    ->label('Nombre del proveedor')
                    ->visible(fn ($get) => $get('tipo') == MovementType::ENTRADA->value),

                TextInput::make('guia_despacho')
                    ->label('Guía de despacho')
                    ->numeric()
                    ->visible(fn ($get) => $get('tipo') == MovementType::ENTRADA->value),

                TextInput::make('comprobante')
                    ->label('Comprobante')
                    ->visible(fn ($get) => in_array($get('tipo'), [MovementType::SALIDA->value, MovementType::TRASLADO->value]))
                    ->numeric(),

                TextInput::make('encargado')
                    ->label('Encargado')
                    ->visible(fn ($get) => in_array($get('tipo'), [MovementType::SALIDA->value, MovementType::TRASLADO->value])),

                Select::make('user_id')
                    ->label('Usuario')
                    ->options(function () {
                        return User::all()->pluck('name', 'id')->toArray();
                    })
                    ->default(fn () => Auth::id())
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Aplicación')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            MovementType::ENTRADA => 'entrada',
                            MovementType::SALIDA => 'salida',
                            MovementType::TRASLADO => 'traslado',
                            MovementType::PREPARACION => 'preparación',
                            default => 'Desconocido',
                        };
                    })
                    ->colors([
                        'entrada' => 'success',
                        'salida' => 'danger',
                        'traslado' => 'warning',
                        'preparacion' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('movement_number')
                    ->label('ID Movimiento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega_origen.name')
                    ->label('Origen')
                    ->Placeholder('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega_destino.name')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->label('Completado'),
                Tables\Columns\TextColumn::make('comprobante')
                    ->label('Comprobante')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('encargado')
                    ->label('Encargado')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bodega')
                    ->label('Bodega')
                    ->options(
                        Warehouse::regular()
                            ->pluck('name', 'id')
                    )
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where(function ($q) use ($data) {
                                $q->where('bodega_origen_id', $data['value'])
                                    ->orWhere('bodega_destino_id', $data['value']);
                            });
                        }
                    })

            ])

            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(fn (Movimiento $record) => $record->is_completed),

                Action::make('complete')
                    ->label('Cerrar')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(function (Movimiento $record) {
                        // Lógica para marcar como completado
                        $record->is_completed = true;
                        $record->save();

                        // Opcional: Registrar una entrada en los logs
                        Log::info("Movimiento ID: {$record->id} ha sido completado por el usuario ID: " . Auth::id());
                    })
                    ->hidden(fn (Movimiento $record) => $record->is_completed),
            ])
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
