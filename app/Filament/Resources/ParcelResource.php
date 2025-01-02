<?php

namespace App\Filament\Resources;



use App\Enums\RoleType;
use App\Filament\Resources\ParcelResource\Pages;
use App\Filament\Resources\ParcelResource\RelationManagers\AplicacionesRelationManager;
use App\Models\Crop;
use App\Models\Parcel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


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
                Forms\Components\TextInput::make('id')
                    ->label('ID')
                    ->readonly(),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->rules(function (Forms\Get $get) {
                        return Rule::unique('parcels', 'name')
                            ->whereNull('deactivated_at') // Omitir parcelas inactivas
                            ->ignore($get('id'));
                    }),
                Forms\Components\Select::make('crop_id')
                    ->label('Cultivo')
                    ->options(Crop::all()->pluck('especie', 'id')->toArray())
                    ->required(),
                Forms\Components\TextInput::make('planting_year')
                    ->label('Año Plantación')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('plants')
                    ->label('Plantas')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('surface')
                    ->label('Superficie')
                    ->suffix('ha')
                    ->required()
                    ->numeric(2),
                Forms\Components\TextInput::make('deactivation_reason')
                    ->label('Motivo de Baja')
                    ->visible(fn($record) => $record !== null && !$record->is_active)
                    ->disabled(),
                Forms\Components\TextInput::make('deactivated_at')
                    ->label('Fecha de Baja')
                    ->visible(fn($record) => $record !== null && !$record->is_active)
                    ->disabled(),
                Forms\Components\TextInput::make('deactivatedBy.name')
                    ->label('Dada de baja por')
                    ->visible(fn($record) => $record !== null && !$record->is_active)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('planting_year')
                    ->label('Año de Plantación')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plants')
                    ->label('Plantas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie')
                    ->searchable()
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('is_active')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Activa'),
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
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Cultivo')
                    ->searchable(true)
                    ->options(Crop::all()->pluck('especie', 'id')->toArray()),
            ])
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
                                RoleType::ADMIN->value,
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
