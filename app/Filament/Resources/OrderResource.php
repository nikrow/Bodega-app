<?php

namespace App\Filament\Resources;

use App\Enums\EppType;
use App\Enums\EquipmentType;
use Filament\Forms;
use App\Models\Crop;
use App\Models\User;
use Filament\Tables;
use App\Models\Field;
use App\Models\Order;
use Filament\Actions;
use App\Models\Parcel;
use Filament\Forms\Form;
use App\Enums\FamilyType;
use App\Models\Warehouse;
use Filament\Tables\Table;
use App\Models\OrderParcel;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\Resources\OrderResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\OrderResource\RelationManagers\OrderLinesRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\OrderParcelRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\ApplicationUsageRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\OrderApplicationRelationManager;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'typ-th-list';
    protected static ?string $navigationGroup = 'Aplicaciones';

    protected static ?string $slug = 'ordenes';
    protected static ?string $pluralModelLabel = 'Ordenes de aplicación';
    protected static ?string $modelLabel = 'Orden';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('General')
                    ->description('Información general de la orden')
                    ->schema([
                        Forms\Components\TextInput::make('orderNumber')
                            ->label('Número de orden')
                            ->readonly(),
                        Forms\Components\Select::make('field_id')
                            ->label('Campo')
                            ->options(function () {
                                return Field::all()->pluck('name', 'id')->toArray();
                            })
                            ->default(fn() => Filament::GetTenant()->id)
                            ->disabled()
                            ->reactive(),

                        Forms\Components\Select::make('user_id')
                            ->label('Responsable técnico')
                            ->options(function () {
                                return User::all()->pluck('name', 'id')->toArray();
                            })
                            ->default(fn() => Auth::id())
                            ->disabled()
                            ->reactive(),
                        Forms\Components\TextInput::make('objective')
                            ->label('Objetivo')
                            ->string()
                            ->required(),
                        Forms\Components\Select::make('crops_id')
                            ->label('Cultivo')
                            ->required()
                            ->reactive()
                            ->options(Crop::all()->pluck('especie', 'id')->toArray()),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Bodega preparación')
                            ->required()
                            ->options(function () {
                                $tenantId = Filament::getTenant()->id;
                                return Warehouse::where('field_id', $tenantId)->pluck('name', 'id');
                            }),

                        Forms\Components\Select::make('family')
                            ->options(FamilyType::class)
                            ->label('Grupo')
                            ->required()
                            ->multiple(),
                        Forms\Components\TextInput::make('wetting')
                            ->label('Mojamiento')
                            ->numeric()
                            ->required(),
                        
                        Forms\Components\Textarea::make('indications')
                            ->label('Indicaciones'),
                        ]),

                Section::make('Cuarteles')
                    ->description('Seleccionar cuarteles a aplicar')
                    ->schema([
                        Forms\Components\TextInput::make('TotalArea')
                            ->label('Superficie total a aplicar')
                            ->readonly()
                            ->suffix('ha')
                            ->reactive()
                            ->numeric(),
                        Section::make('Listado de cuarteles')
                            ->collapsed()
                            ->schema([
                            Forms\Components\CheckboxList::make('parcels')
                                ->label('Cuarteles')
                                ->columns(4)
                                ->searchable()
                                ->reactive()
                                ->gridDirection('row')
                                ->bulkToggleable()
                                ->options(function (callable $get) {
                                    $tenantId = Filament::getTenant()->id;
                                    $cropId = $get('crops_id');
                                    if ($cropId) {
                                        return Parcel::where('crop_id', $cropId)
                                            ->where('field_id', $tenantId)
                                            ->pluck('name', 'id')->toArray();
                                    }
                                    return [];
                                })
                                ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                    $parcelIds = $state ?? [];
                                    $totalArea = Parcel::whereIn('id', $parcelIds)->sum('surface');
                                    $set('TotalArea', $totalArea);
                                })
                                ->saveRelationshipsUsing(function (Order $record, $state) {
                                    $fieldId = $record->field_id;
                                    $userId = Auth::id();

                                    // Verificar relaciones existentes y actualizar los campos
                                    $syncData = collect($state)->mapWithKeys(function ($parcelId) use ($fieldId, $userId, $record) {
                                        $existingPivot = $record->parcels()->wherePivot('parcel_id', $parcelId)->first();

                                        return [
                                            $parcelId => [
                                                'field_id' => $fieldId,
                                                'created_by' => $existingPivot ? $existingPivot->pivot->created_by : $userId,
                                                'updated_by' => $userId,
                                            ]
                                        ];
                                    })->toArray();

                                    // Sincroniza las relaciones con los datos adicionales
                                    $record->parcels()->sync($syncData);
                                })

                                ->afterStateHydrated(function ($state, callable $set, $record) {
                                    if ($record) {
                                        // Obtener las parcelas relacionadas con la orden
                                        $selectedParcels = OrderParcel::where('order_id', $record->id)
                                            ->pluck('parcel_id')
                                            ->toArray();
                                        // Establecer las parcelas seleccionadas
                                        $set('parcels', $selectedParcels);

                                        // Calcular la superficie total
                                        $totalArea = Parcel::whereIn('id', $selectedParcels)->sum('surface');
                                        $set('TotalArea', $totalArea);
                                    }
                                }),
                            ])
                    ]),
                Section::make('Equipamiento')
                    ->description('Equipamiento y elementos de protección a utilizar')
                    ->collapsed()
                        ->schema([
                            Forms\Components\CheckboxList::make('equipment')
                                ->label('Equipamiento')
                                ->columns(2)
                                ->gridDirection('row')
                                ->options(EquipmentType::class)
                                ->required(),
                            Forms\Components\CheckboxList::make('epp')
                                ->label('EPP')
                                ->columns(2)
                                ->bulkToggleable()
                                ->gridDirection('row')
                                ->options(EppType::class)
                                ->required(),
                        ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderNumber')
                    ->label('Número de orden')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_applied_percentage')
                    ->label('% aplicado')
                    ->suffix('%')
                    ->getStateUsing(function ($record) {
                        return $record->total_applied_percentage;
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->label('Completado'),
                Tables\Columns\TextColumn::make('objective')
                    ->label('Objetivo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_completed')
                    ->label('Estado')
                    ->default(0)
                    ->options([
                        1 => 'Completado',
                        0 => 'Pendiente',
                    ])
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->hidden(fn(Order $record) => $record->is_completed),
                    Action::make('complete')
                        ->label('Cerrar')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('observations')
                                ->label('Resumen de la orden')
                                ->required(),
                        ])
                        ->action(function (array $data, Order $record) {
                            try {
                                $record->observations = $data['observations'];
                                $record->is_completed = true;
                                $record->save();
                        
                                Log::info("Orden ID: {$record->id} ha sido completada por el usuario ID: " . Auth::id());
                            } catch (\Exception $e) {
                                Log::error("Error al guardar la orden: {$e->getMessage()}");
                            }
                        })                     
                        ->hidden(fn(Order $record) => $record->is_completed),
                        
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn(Order $record) => $record->orderLines()->count() === 0),
                    Actions\Action::make('downloadPdf')
                        ->label('Orden')
                        ->color('danger')
                        ->icon('phosphor-file-pdf-fill')
                        ->url(fn(Order $record) => route('orders.downloadPdf', $record->id))
                        ->openUrlInNewTab(),
                    Actions\Action::make('bodegaPdf')
                        ->label('Bodega')
                        ->color('warning')
                        ->icon('phosphor-file-pdf-duotone')
                        ->url(fn(Order $record) => route('orders.bodegaPdf', $record->id))
                        ->openUrlInNewTab(),
                ])->button()->size(ActionSize::Small)
            ], position: ActionsPosition::BeforeColumns)

            ->bulkActions([
                    ExportBulkAction::make()
            ]);
    }
    public static function getRelations(): array
    {
        return [
            OrderLinesRelationManager::class,
            OrderApplicationRelationManager::class,
            ApplicationUsageRelationManager::class,
            OrderParcelRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
