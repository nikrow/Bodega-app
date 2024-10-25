<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClimateResource\Pages;
use App\Filament\Resources\ClimateResource\RelationManagers;
use App\Models\Climate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClimateResource extends Resource
{
    protected static ?string $model = Climate::class;

    protected static ?string $navigationIcon = 'heroicon-o-sun';
    protected static ?string $navigationGroup = 'Aplicaciones';
    protected static ?string $navigationTitle = 'Datos climáticos';

    protected static ?string $slug = 'clima';
    protected static ?string $pluralModelLabel = 'Clima';
    protected static ?string $modelLabel = 'Clima';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('wind')
                    ->label('Viento')
                    ->suffix('km/h')
                    ->numeric(),
                Forms\Components\TextInput::make('temperature')
                    ->label('Temperatura')
                    ->suffix('°C')
                    ->numeric(),
                Forms\Components\TextInput::make('humidity')
                    ->label('Humedad')
                    ->suffix('%')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wind')
                    ->label('Viento')
                    ->suffix('km/h')
                    ->numeric(),
                Tables\Columns\TextColumn::make('temperature')
                    ->label('Temperatura')
                    ->suffix('°C')
                    ->numeric(),
                Tables\Columns\TextColumn::make('humidity')
                    ->label('Humedad')
                    ->suffix('%')
                    ->numeric(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

                    Tables\Actions\DeleteBulkAction::make(),

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
            'index' => Pages\ListClimates::route('/'),
            'create' => Pages\CreateClimate::route('/create'),
            'edit' => Pages\EditClimate::route('/{record}/edit'),
        ];
    }
}
