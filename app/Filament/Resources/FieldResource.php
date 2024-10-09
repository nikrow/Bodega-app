<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FieldResource\Pages;
use App\Filament\Resources\FieldResource\RelationManagers;
use App\Models\Field;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class FieldResource extends Resource
{
    protected static ?string $model = Field::class;



    protected static ?string $tenantOwnershipRelationshipName = 'field'; // Cambia 'user' a la relación correcta

    protected static ?string $navigationIcon = 'eos-landscape-o';

    protected static ?string $navigationGroup = 'Anexos';

    protected static ?string $navigationLabel = 'Campos';

    protected static ?string $modelLabel = 'Campo';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                'name' => Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->unique()
                    ->required()
                    ->rules('required'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('created_by.name')
                    ->label('Creado por'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->date('d/m/Y H:i')
                    ->label('Modificado el'),
                Tables\Columns\TextColumn::make('slug')
                    ->sortable()
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true),


            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFields::route('/'),
            'create' => Pages\CreateField::route('/create'),
            'edit' => Pages\EditField::route('/{record}/edit'),
        ];
    }

    public static function query(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        // Obtener los IDs de los fields (bodegas) asignados al usuario
        $fieldIds = $user->fields->pluck('id');

        // Filtrar las bodegas que tienen esos IDs
        return $query->whereIn('field_id', $fieldIds);
    }

}
