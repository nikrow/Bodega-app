<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Crop;
use Filament\Tables;
use App\Models\Parcel;
use App\Enums\RoleType;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PlantingScheme;
use Filament\Facades\Filament;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use App\Filament\Imports\ParcelImporter;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\View\TablesRenderHook;
use App\Filament\Resources\ParcelResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use App\Filament\Resources\ParcelResource\RelationManagers\AplicacionesRelationManager;
use App\Filament\Resources\ParcelResource\RelationManagers\ParcelCropDetailsRelationManager;

class ParcelResource extends Resource
{
    protected static ?string $model = Parcel::class;

    protected static ?string $navigationIcon = 'eos-dashboard';

    protected static ?string $navigationGroup = 'Anexos';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Cuartel';

    protected static ?string $modelLabel = 'Cuartel';

    protected static ?string $pluralModelLabel = 'Cuarteles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'cuarteles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Cuartel')
                    ->columnSpan('full')
                    ->tabs([
                        Tabs\Tab::make('Información General')
                            ->schema([
                                Section::make('Detalles del Cuartel')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('id')
                                            ->label('ID')
                                            ->readonly(),
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->rules(function (Forms\Get $get) {
                                                return Rule::unique('parcels', 'name')
                                                    ->whereNull('deactivated_at')
                                                    ->ignore($get('id'));
                                            }),
                                        Forms\Components\TextInput::make('tank')
                                            ->label('Estanque')
                                            ->nullable()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('crop_id')
                                            ->label('Cultivo')
                                            ->options(Crop::all()->pluck('especie', 'id')->toArray())
                                            ->required()
                                            ->reactive(),
                                        Forms\Components\TextInput::make('planting_year')
                                            ->label('Año Plantación')
                                            ->numeric()
                                            ->required(),
                                        Forms\Components\TextInput::make('plants')
                                            ->label('Plantas')
                                            ->numeric()
                                            ->required(),
                                        Forms\Components\TextInput::make('surface')
                                            ->label('Superficie Total')
                                            ->suffix('ha')
                                            ->required()
                                            ->numeric()
                                            ->rules(['min:0']),
                                        Forms\Components\TextInput::make('sdp')
                                            ->label('SDP')
                                            ->nullable()
                                            ->maxLength(255),
                                        Forms\Components\Hidden::make('field_id')
                                            ->default(Filament::getTenant()->id),
                                    ]),
                            ]),
                        Tabs\Tab::make('Detalles de Variedades y Portainjertos')
                            ->schema([
                                Section::make('Variedades y Portainjertos')
                                    ->schema([
                                        Forms\Components\TextInput::make('total_variety_surface')
                                            ->label('Superficie Total de Variedades')
                                            ->suffix('ha')
                                            ->numeric()
                                            ->readonly()
                                            ->default(0)
                                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $record) {
                                                if ($record && $record->parcelCropDetails) {
                                                    $total = $record->parcelCropDetails->sum('surface');
                                                    $component->state($total);
                                                }
                                            }),
                                        Repeater::make('parcelCropDetails')
                                            ->label('Subsectores, Variedades, MP y Portainjertos')
                                            ->relationship('parcelCropDetails')
                                            ->default([])
                                            ->schema([
                                                Forms\Components\Hidden::make('crop_id')
                                                    ->default(fn (Forms\Get $get) => $get('../../crop_id')),
                                                Forms\Components\TextInput::make('subsector')
                                                    ->label('Subsector'),
                                                Forms\Components\Select::make('variety_id')
                                                    ->label('Variedad')
                                                    ->options(function (Forms\Get $get) {
                                                        $cropId = $get('../../crop_id');
                                                        return $cropId
                                                            ? \App\Models\Variety::where('crop_id', $cropId)->pluck('name', 'id')->toArray()
                                                            : [];
                                                    })
                                                    ->nullable()
                                                    ->reactive(),
                                                Forms\Components\Select::make('rootstock_id')
                                                    ->label('Portainjerto')
                                                    ->options(function (Forms\Get $get) {
                                                        $cropId = $get('../../crop_id');
                                                        return $cropId
                                                            ? \App\Models\Rootstock::where('crop_id', $cropId)->pluck('name', 'id')->toArray()
                                                            : [];
                                                    })
                                                    ->nullable()
                                                    ->reactive(),
                                                
                                                Forms\Components\Select::make('planting_scheme_id')
                                                ->label('Marco de Plantación')
                                                ->options(function () {
                                                    return PlantingScheme::pluck('scheme', 'id')->toArray();
                                                })
                                                ->searchable()
                                                ->nullable()
                                                ->reactive()
                                                ->hintAction(
                                                    Forms\Components\Actions\Action::make('add_planting_scheme')
                                                        ->label('Agregar marco')
                                                        ->icon('heroicon-o-plus')
                                                        ->modalHeading('Crear Nuevo Marco de Plantación')
                                                        ->form([
                                                            Forms\Components\TextInput::make('new_scheme')
                                                                ->label('Seguir el formato: 2,5 x 1,5')
                                                                ->required()
                                                                ->maxLength(255)
                                                                ->rules(['unique:planting_schemes,scheme']),
                                                        ])
                                                        ->action(function (array $data, Forms\Set $set) {
                                                            $newScheme = trim($data['new_scheme']);
                                                            $createdScheme = PlantingScheme::create(['scheme' => $newScheme]);
                                                            $set('planting_scheme_id', $createdScheme->id);
                                                            Notification::make()
                                                                ->title('Éxito')
                                                                ->body('El nuevo marco de plantación ha sido creado.')
                                                                ->success()
                                                                ->send();
                                                        })
                                                ),
                                                Forms\Components\Select::make('irrigation_system')
                                                    ->label('Sistema de Riego')
                                                    ->options([
                                                        'gotero' => 'Gotero',
                                                        'aspersor' => 'Aspersor',
                                                        'microjet' => 'Microjet',
                                                        'otro' => 'Otro',
                                                    ])
                                                    ->required(),
                                                Forms\Components\TextInput::make('surface')
                                                    ->label('Superficie')
                                                    ->suffix('ha')
                                                    ->numeric()
                                                    ->nullable()
                                                    ->rules(['min:0']),
                                            ])
                                            ->columns(6)
                                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                                $totalSurface = collect($get('parcelCropDetails'))->sum('surface');
                                                $set('total_variety_surface', $totalSurface);
                                                $parcelSurface = $get('surface');
                                                if ($parcelSurface && $totalSurface > $parcelSurface) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('La suma de las superficies de las variedades no puede superar la superficie total del cuartel.')
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                            ->rules([
                                                function (Forms\Get $get) {
                                                    return function (string $attribute, $value, $fail) use ($get) {
                                                        $totalSurface = collect($get('parcelCropDetails'))->sum('surface');
                                                        $parcelSurface = $get('surface');
                                                        if ($parcelSurface && $totalSurface > $parcelSurface) {
                                                            $fail('La suma de las superficies de las variedades no puede superar la superficie total del cuartel.');
                                                        }
                                                    };
                                                },
                                            ]),
                                    ]),
                            ]),
                        Tabs\Tab::make('Información Adicional')
                            ->schema([
                                Section::make('Información Adicional')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('deactivation_reason')
                                            ->label('Motivo de Baja')
                                            ->visible(fn ($record) => $record !== null && !$record->is_active)
                                            ->disabled(),
                                        Forms\Components\TextInput::make('deactivated_at')
                                            ->label('Fecha de Baja')
                                            ->visible(fn ($record) => $record !== null && !$record->is_active)
                                            ->disabled(),
                                        Forms\Components\TextInput::make('deactivatedBy.name')
                                            ->label('Dada de baja por')
                                            ->visible(fn ($record) => $record !== null && !$record->is_active)
                                            ->disabled(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('tank')
                    ->label('Estanque')
                    ->searchable()
                    ->disabled(fn (Parcel $record): bool => !$record->is_active)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->disabled(fn (Parcel $record): bool => !$record->is_active)
                    ->sortable(),
                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextInputColumn::make('planting_year')
                    ->label('Año de Plantación')
                    ->searchable()
                    ->disabled(fn (Parcel $record): bool => !$record->is_active)
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('plants')
                    ->label('Plantas')
                    ->searchable()
                    ->disabled(fn (Parcel $record): bool => !$record->is_active)
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('surface')
                    ->label('Superficie')
                    ->searchable()
                    ->disabled(fn (Parcel $record): bool => !$record->is_active)
                    ->sortable(),
                Tables\Columns\TextColumn::make('parcelCropDetails')
                    ->label('Detalles del Subsector')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state, $record) {
                        $details = [];
                        // Use the null-safe operator to check for a valid collection
                        foreach ($record->parcelCropDetails?->all() ?? [] as $detail) {
                            $parts = [];
                            
                            // Verificamos si la relación y la propiedad existen antes de agregarlas
                            if ($detail->subsector) {
                                $parts[] = 'Subsector: ' . $detail->subsector;
                            }
                            if ($detail->variety) {
                                $parts[] = 'Variedad: ' . $detail->variety->name;
                            }
                            if ($detail->rootstock) {
                                $parts[] = 'Portainjerto: ' . $detail->rootstock->name;
                            }
                            if ($detail->plantingScheme) {
                                $parts[] = 'Marco: ' . $detail->plantingScheme->scheme;
                            }
                            if ($detail->surface) {
                                $parts[] = 'Superficie: ' . $detail->surface . ' ha';
                            }
                            $details[] = implode(', ', $parts);
                        }
                        return implode(' | ', $details);
                    }),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Activa')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deactivated_at')
                    ->label('Fecha de Baja')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deactivatedBy.name')
                    ->label('Dada de baja por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas')
                    ->default(true),
                Tables\Filters\SelectFilter::make('tank')
                    ->label('Estanque')
                    ->options(
                        Parcel::whereNotNull('tank')->distinct()->pluck('tank', 'tank')->sort()->toArray()
                    )
                    ->searchable(true),
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Cultivo')
                    ->searchable(true)
                    ->options(Crop::all()->pluck('especie', 'id')->sort()->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->label('Ver')
                        ->color('primary'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil')
                        ->label('Editar')
                        ->color('warning')
                        ->visible(fn ($record) => $record->is_active),
                    Tables\Actions\Action::make('darDeBaja')
                        ->label('Dar de Baja')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->modalHeading('Motivo de Baja')
                        ->form([
                            Forms\Components\Textarea::make('deactivation_reason')
                                ->label('Motivo')
                                ->required(),
                        ])
                        ->action(function (Parcel $record, array $data) {
                            $record->update([
                                'is_active' => false,
                                'deactivated_at' => now(),
                                'deactivated_by' => Auth::id(),
                                'deactivation_reason' => $data['deactivation_reason'],
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(function () {
                            $user = Auth::user();
                            return in_array($user->role, [
                                RoleType::ADMIN,
                            ]);
                        })
                        ->hidden(fn ($record) => !$record->is_active),
                ])
            ])
            ->bulkActions([
                ExportBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AplicacionesRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParcels::route('/'),
            'create' => Pages\CreateParcel::route('/create'),
            'edit' => Pages\EditParcel::route('/{record}/edit'),
            'view' => Pages\ViewParcel::route('/{record}'),
        ];
    }
}   