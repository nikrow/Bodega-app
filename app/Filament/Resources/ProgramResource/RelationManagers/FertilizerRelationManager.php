<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\FertilizerMapping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class FertilizerRelationManager extends RelationManager
{
    protected static string $relationship = 'fertilizers';
    protected static ?string $title = 'Fertilizantes';
    protected static ?string $modelLabel = 'Fertilizante';
    protected static ?string $pluralModelLabel = 'Fertilizantes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('fertilizer_mapping_id')
                    ->relationship('fertilizerMapping', 'fertilizer_name')
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->label('Fertilizante')
                    ->afterStateUpdated(function (callable $set, $state, $get) {
                        $mapping = FertilizerMapping::find($state);
                        if ($mapping) {
                            $set('dilution_factor', $mapping->dilution_factor);
                        }
                    })
                    ->required(),
                Forms\Components\TextInput::make('dilution_factor')
                    ->numeric()
                    ->label('Factor de Dilución')
                    ->readOnly(true)
                    ->reactive(),
                Forms\Components\TextInput::make('units_per_ha')
                    ->numeric()
                    ->label('Unidades por Hectárea'),
                Forms\Components\TextInput::make('application_quantity')
                    ->numeric()
                    ->label('Cantidad de Aplicaciones')
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('fertilizerMapping.fertilizer_name')
                    ->label('Fertilizante'),
                Tables\Columns\TextColumn::make('dilution_factor')
                    ->label('Factor de Dilución'),
                Tables\Columns\TextColumn::make('units_per_ha')
                    ->label('Unidades por Hectárea'),
                Tables\Columns\TextColumn::make('application_quantity')
                    ->label('Aplicaciones previstas'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
