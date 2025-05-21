<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Irrigation;
use Carbon\CarbonInterval;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\IrrigationResource\Pages;
use App\Filament\Resources\IrrigationResource\RelationManagers;

class IrrigationResource extends Resource
{
    protected static ?string $model = Irrigation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('parcel_id')
                    ->label('Cuartel')
                    ->required(),
                Forms\Components\TextInput::make('time')
                    ->label('Hora')
                    ->required()
                    ->default(now()->format('H:i')),
                Forms\Components\TextInput::make('duration')
                    ->label('Duración')
                    ->numeric(),
                Forms\Components\TextInput::make('quantity_m3')
                    ->label('Cantidad (m3)')
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->label('Tipo')
                    ->maxLength(255),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parcel.name')
                    ->sortable()
                    ->searchable()
                    ->label('Cuartel')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duración')
                    ->formatStateUsing(function ($state) {
                        $hours = floor($state / 3600);
                        $minutes = floor(($state % 3600) / 60);
                        $seconds = $state % 60;
                        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_m3')
                    ->label('Cantidad (m3)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->label('Actualizado por')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Actualizado el')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIrrigations::route('/'),
            'create' => Pages\CreateIrrigation::route('/create'),
            'edit' => Pages\EditIrrigation::route('/{record}/edit'),
        ];
    }
}
