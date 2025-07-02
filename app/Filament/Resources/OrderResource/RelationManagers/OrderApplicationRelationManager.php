<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Climate;
use App\Models\OrderParcel;
use App\Models\Parcel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class OrderApplicationRelationManager extends RelationManager
{
    protected static string $relationship = 'orderApplications';

    protected static ?string $title = 'Aplicaciones en terreno';
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
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        // Obtener la superficie del cuartel seleccionado
                        $parcelId = $get('parcel_id');
                        if ($parcelId) {
                            $parcel = Parcel::find($parcelId);
                            $set('parcel_surface', $parcel->surface);
                        } else {
                            $set('parcel_surface', null);
                        }

                        // Recalcular superficie y porcentaje
                        $this->calculateSurfaceAndValidate($get, $set);
                    })

                    ->searchable(),

                Forms\Components\Hidden::make('parcel_surface'),
                Forms\Components\TextInput::make('liter')
                    ->label('Litros aplicados')
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $this->calculateSurfaceAndValidate($get, $set);
                    }),

                Forms\Components\TextInput::make('wetting')
                    ->label('Mojamiento')
                    ->suffix('l/ha')
                    ->default(fn () => $this->ownerRecord->wetting)
                    ->numeric()
                    ->debounce(500)
                    ->live(onBlur: true)
                    ->required()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $this->calculateSurfaceAndValidate($get, $set);
                    }),

                Forms\Components\TextInput::make('wind_speed')
                    ->label('Viento')
                    ->suffix('km/h')
                    ->required()
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
                Forms\Components\TextInput::make('application_percentage')
                    ->label('Porcentaje del cuartel aplicado')
                    ->suffix('%')
                    ->numeric(),

                Forms\Components\Select::make('applicators')
                    ->label('Aplicadores')
                    ->multiple()
                    ->required()
                    ->relationship('applicators', 'name')
                    ->searchable()
                    ->preload(),

            ]);
    }

    private function calculateSurfaceAndValidate(callable $get, callable $set)
    {
        $wetting = $get('wetting');
        $liter = $get('liter');

        $surfaceApplied = ($wetting > 0) ? ($liter / $wetting) : 0;
        $set('surface', $surfaceApplied);

        // Validar la superficie aplicada contra la superficie del cuartel
        $parcelSurface = $get('parcel_surface') ?? 0;
        if ($surfaceApplied > $parcelSurface) {
            $set('surface_warning', 'La superficie aplicada excede la superficie del cuartel.');
        } else {
            $set('surface_warning', null);
        }

        // Calcular el porcentaje de aplicación
        if ($parcelSurface > 0) {
            $percentage = ($surfaceApplied / $parcelSurface) * 100;
            $set('application_percentage', round($percentage, 2));
        } else {
            $set('application_percentage', null);
        }
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
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ','),
                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie aplicada')
                    ->suffix('  ha')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: '.', decimalSeparator: ','),
                Tables\Columns\TextColumn::make('application_percentage')
                    ->label('Porcentaje del cuartel aplicado')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: '.', decimalSeparator: ','),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('wetting')
                    ->label('Mojamiento')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('wind_speed')
                    ->label('Viento')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('temperature')
                    ->label('Temperatura')
                    ->suffix('  °C')
                    ->numeric(decimalPlaces: 1, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('moisture')
                    ->label('Humedad')
                    ->suffix('  %')
                    ->numeric(decimalPlaces: 1, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('applicators_details')
                ->label('Aplicadores')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar aplicación'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make()->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(date('Y-m-d') . ' - Aplicaciones ')
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                        ->withColumns([
                            Column::make('created_at')->heading('Fecha')
                                ->formatStateUsing(function ($state) {
                                    $date = \Carbon\Carbon::parse($state);
                                    return \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($date);
                                })
                                ->format(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY),
                            Column::make('id')->heading('ID'),
                            Column::make('parcel.name')->heading('Cuartel'),
                            Column::make('liter')->heading('Litros Aplicados'),
                            Column::make('surface')->heading('Superficie aplicada'),
                            Column::make('application_percentage')->heading('Porcentaje del cuartel aplicado'),
                            Column::make('createdBy.name')->heading('Creado por'),
                            Column::make('wetting')->heading('Mojamiento'),
                            Column::make('wind_speed')->heading('Viento'),
                            Column::make('temperature')->heading('Temperatura'),
                            Column::make('moisture')->heading('Humedad'),
                            Column::make('applicators_details')->heading('Aplicadores'),
                        ]),
                ]),
            ]);
    }
}
