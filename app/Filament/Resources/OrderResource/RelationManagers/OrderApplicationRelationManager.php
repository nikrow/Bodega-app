<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\StatusType;
use App\Models\Climate;
use App\Models\OrderParcel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderApplicationRelationManager extends RelationManager
{
    protected static string $relationship = 'orderApplications';

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
                    ->default(fn () => $this->ownerRecord->wetting)
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
                    ->numeric()
                    ->default(fn () => optional($this->getTodayClimateData())->wind),

                Forms\Components\TextInput::make('temperature')
                    ->label('Temperatura')
                    ->numeric()
                    ->suffix('°C')
                    ->required()
                    ->default(fn () => optional($this->getTodayClimateData())->temperature),

                Forms\Components\TextInput::make('moisture')
                    ->label('Humedad')
                    ->numeric()
                    ->suffix('%')
                    ->required()
                    ->default(fn () => optional($this->getTodayClimateData())->humidity),

                Forms\Components\TextInput::make('surface')
                    ->label('Superficie aplicada')
                    ->default(0)
                    ->readonly()
                    ->suffix('has')
                    ->numeric()
                    ->reactive(),
                Forms\Components\Select::make('applicators')
                    ->label('Aplicadores')
                    ->multiple()
                    ->relationship('applicators', 'name')
                    ->searchable()
                    ->preload(),

            ]);
    }
    protected function getTodayClimateData()
    {
        // Obtener los datos climáticos de la fecha de hoy

        return Climate::whereDate('created_at', today())->latest('created_at')->first();
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Aplicación')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('parcel.name')
                    ->label('Cuartel')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('liter')
                    ->label('Litros aplicados')
                    ->suffix('  l')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ',')
                    ->searchable(),
                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie aplicada')
                    ->suffix('  ha')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: '.', decimalSeparator: ',')
                    ->searchable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('wetting')
                    ->label('Mojamiento')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('wind_speed')
                    ->label('Viento')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('temperature')
                    ->label('Temperatura')
                    ->suffix('  °C')
                    ->numeric(decimalPlaces: 1, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('moisture')
                    ->label('Humedad')
                    ->suffix('  %')
                    ->numeric(decimalPlaces: 1, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicators.name')
                ->label('Aplicadores')
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
