<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Field;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Services\WiseconnService;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\FieldResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FieldResource\RelationManagers;

class FieldResource extends Resource
{
    protected static ?string $model = Field::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationGroup = 'Anexos';
    protected static ?string $navigationLabel = 'Campos';
    protected static ?string $modelLabel = 'Campo';
    protected static ?string $pluralModelLabel = 'Campos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('api_key')
                    ->label('Clave API de Wiseconn')
                    ->password() 
                    ->revealable()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('wiseconn_farm_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Campo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Fecha de Creación')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Fecha de Actualización')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('wiseconn_farm_id')
                    ->numeric()
                    ->label('ID de Farm Wiseconn')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('sync_zones')
                    ->label('Sincronizar Zonas')
                    ->action(function (Field $record) {
                        app(WiseconnService::class)->syncZones($record);
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Zonas sincronizadas exitosamente'),
            ])
            ->bulkActions([
                
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
            'index' => Pages\ListFields::route('/'),
            'create' => Pages\CreateField::route('/create'),
            'edit' => Pages\EditField::route('/{record}/edit'),
        ];
    }
}
