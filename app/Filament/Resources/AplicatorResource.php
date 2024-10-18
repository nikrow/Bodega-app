<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AplicatorResource\Pages;
use App\Filament\Resources\AplicatorResource\RelationManagers;
use App\Models\Aplicator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AplicatorResource extends Resource
{
    protected static ?string $model = Aplicator::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $tenantOwnershipRelationshipName = 'field';
    protected static ?string $navigationGroup = 'Anexos';
    protected static ?string $navigationLabel = 'Aplicadores';
    protected static ?string $modelLabel = 'Aplicador';
    protected static ?string $pluralModelLabel = 'Aplicadores';
    protected static ?string $slug = 'aplicadores';
    protected static ?int $navigationSort = 80;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->unique()
                    ->required(),
                Forms\Components\TextInput::make('rut')
                    ->label('RUT')
                    ->unique()
                    ->required(),
                Forms\Components\TextInput::make('tractor')
                    ->label('Tractor')
                    ->required(),
                Forms\Components\TextInput::make('equipment')
                    ->label('Equipo')
                    ->required(),
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
                Tables\Columns\TextColumn::make('rut')
                    ->label('RUT')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tractor')
                    ->label('Tractor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('equipment')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Editado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('created_at')
                    ->label('Creado el')
                    ->date('d/m/Y')
                    ->sortable(),
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
            'index' => Pages\ListAplicators::route('/'),
            'create' => Pages\CreateAplicator::route('/create'),
            'edit' => Pages\EditAplicator::route('/{record}/edit'),
        ];
    }
}
