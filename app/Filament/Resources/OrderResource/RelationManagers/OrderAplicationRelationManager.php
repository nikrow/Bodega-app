<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderParcel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrderAplicationRelationManager extends RelationManager
{
    protected static string $relationship = 'orderAplications';

    protected static ?string $title = 'Aplicaciones';
    protected static ?string $modelLabel = 'Aplicación';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('parcel_id')
                    ->label('Cuartel')
                    ->required()
                    ->options(fn () => OrderParcel::with('parcel')
                        ->where('order_id', $this->ownerRecord->id)
                        ->get()
                        ->pluck('parcel.name', 'parcel_id')
                        ->toArray()
                    )
                    ->searchable(),

                Forms\Components\TextInput::make('liter')
                    ->label('Litros aplicados')
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        // Recalcular el valor de 'surface' cuando cambie 'liter'
                        $wetting = $get('wetting');
                        $liter = $get('liter');

                        $set('surface', ($wetting > 0) ? ($liter / $wetting) : 0);
                    }),

                Forms\Components\TextInput::make('wetting')
                    ->label('Mojamiento')
                    ->suffix('l/ha')
                    ->numeric()
                    ->live(onBlur: true)
                    ->required()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        // Recalcular el valor de 'surface' cuando cambie 'wetting'
                        $wetting = $get('wetting');
                        $liter = $get('liter');

                        $set('surface', ($wetting > 0) ? ($liter / $wetting) : 0);
                    }),



                Forms\Components\TextInput::make('wind_speed')
                    ->label('Viento')
                    ->suffix('km/h')
                    ->numeric(),

                Forms\Components\TextInput::make('temperature')
                    ->label('Temperatura')
                    ->numeric()
                    ->suffix('°C')
                    ->required(),

                Forms\Components\TextInput::make('moisture')
                    ->label('Humedad')
                    ->numeric()
                    ->suffix('%')
                    ->required(),
                Forms\Components\TextInput::make('surface')
                    ->label('Superficie aplicada')
                    ->default(0)
                    ->readonly()
                    ->suffix('has')
                    ->numeric()
                    ->reactive(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID aplicación')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('parcel.name')
                    ->label('Cuartel')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('liter')
                    ->label('Litros aplicados')
                    ->searchable(),
                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie aplicada')
                    ->searchable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('wetting')
                    ->label('Mojamiento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('wind_speed')
                    ->label('Viento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('temperature')
                    ->label('Temperatura')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('moisture')
                    ->label('Humedad')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
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
