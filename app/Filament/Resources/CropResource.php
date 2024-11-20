<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Filament\Resources\CropResource\RelationManagers;
use App\Models\Crop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;


class CropResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = Crop::class;

    protected static ?string $navigationIcon = 'phosphor-plant';

    protected static ?string $navigationGroup = 'Anexos';

    protected static ?string $navigationLabel = 'Cultivos';

    protected static ?string $modelLabel = 'Cultivo';

    protected static ?int $navigationSort = 30;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('especie')
                    ->label('Cultivo')
                    ->required()
                    ->unique()
                    ->rules('required', 'max:255'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('especie')
                ->label('Cultivo')
                ->searchable()
                ->sortable(),
                Tables\columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y H:i')
                    ->sortable(),
                Tables\columns\TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->date('d/m/Y H:i')
                    ->label('Modificado el'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrops::route('/'),
            'create' => Pages\CreateCrop::route('/create'),
            'edit' => Pages\EditCrop::route('/{record}/edit'),
        ];
    }

}
