<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WharehouseResource\Pages;
use App\Filament\Resources\WharehouseResource\RelationManagers;
use App\Models\Category;
use App\Models\Field;
use App\Models\Wharehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WharehouseResource extends Resource
{
    protected static ?string $model = Wharehouse::class;

    protected static ?string $navigationIcon = 'fas-warehouse';

    protected static ?string $navigationGroup = 'Anexos';

    protected static ?string $navigationLabel = 'Bodegas';

    protected static ?string $modelLabel = 'Bodega';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nombre')
                    ->unique()
                    ->rules('required', 'max:255'),
                forms\Components\Select::make('field_id')
                    ->label('Campo')
                    ->searchable()
                    ->options(Field::all()->pluck('name', 'id')->toArray())
                    ->required()
                    ->rules('required'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('field.name')
                    ->label('Campo')
                    ->searchable()
                    ->sortable(),
                TableS\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWharehouses::route('/'),
            'create' => Pages\CreateWharehouse::route('/create'),
            'edit' => Pages\EditWharehouse::route('/{record}/edit'),
        ];
    }
}
