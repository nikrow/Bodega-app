<?php

namespace App\Filament\Resources;

use App\Enums\FamilyType;
use App\Enums\StatusType;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Resources\OrderResource\RelationManagers\ApplicationUsageRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\OrderApplicationRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\OrderLinesRelationManager;

use App\Models\Applicator;
use App\Models\Crop;
use App\Models\Field;
use App\Models\Order;
use App\Models\Parcel;
use App\Models\OrderParcel;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use function Laravel\Prompts\multiselect;

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
            ->columns('full')

            ->schema([

                Wizard::make([
                    Wizard\Step::make('Orden')

                        ->schema([
                            Forms\Components\TextInput::make('orderNumber')
                                ->label('Número de orden')
                                ->readonly(),
                            Forms\Components\Select::make('field_id')
                                ->label('Campo')
                                ->options(function () {
                                    return Field::all()->pluck('name', 'id')->toArray();
                                })
                                ->default(fn () => Filament::GetTenant()->id)
                                ->disabled()
                                ->reactive(),

                            Forms\Components\Select::make('user_id')
                                ->label('Responsable técnico')
                                ->options(function () {
                                    return User::all()->pluck('name', 'id')->toArray();
                                })
                                ->default(fn () => Auth::id())
                                ->disabled()
                                ->reactive(),

                            Forms\Components\Select::make('crops_id')
                                ->label('Cultivo')
                                ->options(Crop::all()->pluck('especie', 'id')->toArray()),
                            Forms\Components\Select::make('warehouse_id')
                                ->label('Bodega preparación')
                                ->required()
                                ->options(function () {
                                    $tenantId = Filament::getTenant()->id;
                                    return Warehouse::where('field_id', $tenantId)->pluck('name', 'id');
                                }),

                            Forms\Components\Select::make('family')
                                ->options(collect(FamilyType::cases())
                                    ->mapWithKeys(fn(FamilyType $type) => [$type->value => $type->getLabel()])
                                    ->toArray())
                                ->label('Grupo')
                                ->multiple(),
                            Forms\Components\TextInput::make('wetting')
                                ->label('Mojamiento')
                                ->rules('required'),

                        ]),
                    Wizard\Step::make('Cuarteles')
                        ->columns(2)
                        ->schema([
                            Forms\Components\CheckboxList::make('parcels')
                                ->label('Cuarteles')
                                ->columns(2)
                                ->searchable()
                                ->gridDirection('row')
                                ->bulkToggleable()
                                ->options(function (callable $get) {
                                    $tenantId = Filament::getTenant()->id;
                                    $cropId = $get('crops_id');
                                    if ($cropId) {
                                        return Parcel::where('crop_id', $cropId)->pluck('name', 'id')->toArray();
                                    }
                                    return [];
                                })
                                ->saveRelationshipsUsing(function (Order $record, $state) {
                                    if (!is_array($state)) {
                                        $state = json_decode($state, true);
                                    }

                                    // Eliminar todas las relaciones previas
                                    OrderParcel::where('order_id', $record->id)->delete();

                                    // Crear las nuevas relaciones
                                    foreach ($state as $parcelId) {
                                        OrderParcel::create([
                                            'order_id' => $record->id,
                                            'parcel_id' => $parcelId,
                                            'field_id' => $record->field_id,
                                            'created_by' => Auth::id(),
                                            'updated_by' => Auth::id(),
                                        ]);
                                    }
                                })
                                ->afterStateHydrated(function ($state, callable $set, $record) {
                                    if ($record) {
                                        // Obtener las parcelas relacionadas con la orden
                                        $selectedParcels = OrderParcel::where('order_id', $record->id)
                                            ->pluck('parcel_id')
                                            ->toArray();
                                        // Establecer las parcelas seleccionadas
                                        $set('parcels', $selectedParcels);
                                    }
                                })

                        ]),
                    Wizard\Step::make('Equipamientos')
                        ->columns(2)
                        ->schema([

                            Forms\Components\CheckboxList::make('equipment')
                                ->label('Equipamiento')
                                ->columns(2)
                                ->gridDirection('row')
                                ->options([
                                    'turbonebulizador' => 'Turbonebulizador',
                                    'turbocañon' => 'Turbocáñon',
                                    'helicoptero' => 'Helicoptero',
                                    'dron' => 'Dron',
                                    'caracol' => 'Caracol',
                                    'bomba_espalda' => 'Bomba espalda',
                                    'barra_levera_parada' => 'Barra levera parada',
                                    'azufrador' => 'Azufrador',

                                ])
                                ->rules('required'),
                            Forms\Components\CheckboxList::make('epp')
                                ->label('EPP')
                                ->columns(2)
                                ->bulkToggleable()
                                ->gridDirection('row')
                                ->options([
                                    'traje_aplicacion' => 'Traje de aplicación',
                                    'guantes' => 'Guantes',
                                    'botas' => 'Botas',
                                    'protector_auditivo' => 'Protector auditivo',
                                    'anteojos' => 'Anteojos',
                                    'antiparras' => 'Antiparras',
                                    'mascara_filtro' => 'Mascara de filtro',
                                ])

                                ->rules('required'),

                        ]),
                    Wizard\Step::make('Aplicadores')
                        ->columns(2)
                        ->schema([
                            Forms\Components\CheckboxList::make('applicators')
                                ->label('Aplicadores')
                                ->bulkToggleable()
                                ->columns(2)
                                ->gridDirection('row')
                                ->options(fn() => Applicator::all()->pluck('name', 'id')->toArray())
                            ])
                        ])
                    ->skippable(),

            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('crop.especie')
            ->groups([
                Group::make('crop.especie')
                    ->collapsible()
                    ->label('Cultivo'),
            ])
            ->columns([
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
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->label('Completado'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->date('d/m/Y')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_completed')
                    ->label('Completado')
                    ->options([
                        'true' => 'Completado',
                        'false' => 'Pendiente',
                    ])

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(fn (Order $record) => $record->is_completed),

                Action::make('complete')
                    ->label('Cerrar')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        // Lógica para marcar como completado
                        $record->is_completed = true;
                        $record->save();

                        // Opcional: Registrar una entrada en los logs
                        Log::info("Movimiento ID: {$record->id} ha sido completado por el usuario ID: " . Auth::id());
                    })
                    ->hidden(fn (Order $record) => $record->is_completed),

            ])
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

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),

        ];
    }
}
