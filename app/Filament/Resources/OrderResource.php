<?php

namespace App\Filament\Resources;

use App\Enums\FamilyType;
use App\Enums\StatusType;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Resources\OrderResource\RelationManagers\OrderAplicationRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\OrderLinesRelationManager;

use App\Models\Crop;
use App\Models\Field;
use App\Models\Order;
use App\Models\Parcel;
use App\Models\OrderParcel;
use App\Models\User;
use App\Models\Wharehouse;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
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
                                ->label('Encargado')
                                ->options(function () {
                                    return User::all()->pluck('name', 'id')->toArray();
                                })
                                ->default(fn () => Auth::id())
                                ->disabled()
                                ->reactive(),

                            Forms\Components\Select::make('crops_id')
                                ->label('Cultivo')
                                ->options(Crop::all()->pluck('especie', 'id')->toArray()),
                            Forms\Components\Select::make('wharehouse_id')
                                ->label('Bodega preparación')
                                ->required()
                                ->options(Wharehouse::all()->pluck('name', 'id')->toArray()),

                            Forms\Components\Select::make('family')
                                ->options(collect(FamilyType::cases())
                                    ->mapWithKeys(fn(FamilyType $type) => [$type->value => $type->getLabel()])
                                    ->toArray())
                                ->label('Grupo')
                                ->multiple(),


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
                            Forms\Components\TextInput::make('wetting')
                                ->label('Mojamiento')
                                ->rules('required'),
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

                            ])

                ]) ->skippable()
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
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            StatusType::PENDIENTE => 'Pendiente',
                            StatusType::ENPROCESO => 'En proceso',
                            StatusType::COMPLETO => 'Completo',
                            StatusType::CANCELADO => 'Cancelado',
                            default => 'Desconocido',

                        };
                    })
                    ->colors([
                        'Pendiente' => 'danger',
                        'En proceso' => 'warning',
                        'Completo' => 'success',
                        'Cancelado' => 'danger',

                    ])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->searchable()
                    ->date('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        StatusType::PENDIENTE->value => 'Pendiente',
                        StatusType::ENPROCESO->value => 'En proceso',
                        StatusType::COMPLETO->value => 'Completo',
                        StatusType::CANCELADO->value => 'Cancelado',
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderLinesRelationManager::class,
            OrderAplicationRelationManager::class,


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
