<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderParcel;
use App\Models\Parcel;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Facades\Filament;

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
                        $parcelId = $get('parcel_id');
                        if ($parcelId) {
                            $parcel = Parcel::find($parcelId);
                            $set('parcel_surface', $parcel->surface);
                        } else {
                            $set('parcel_surface', null);
                        }
                        $this->calculateSurfaceAndValidate($get, $set);
                    })
                    ->searchable(),

                Forms\Components\Hidden::make('parcel_surface'),
                Forms\Components\TextInput::make('liter')
                    ->label('Litros aplicados')
                    ->required()
                    ->numeric()
                    ->debounce(300)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $this->calculateSurfaceAndValidate($get, $set);
                    }),

                Forms\Components\TextInput::make('wetting')
                    ->label('Mojamiento')
                    ->suffix('l/ha')
                    ->default(fn () => $this->ownerRecord->wetting)
                    ->numeric()
                    ->debounce(300)
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
                    ->default(fn () => optional($this->getTodayClimateData())->wind_speed),

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

        $parcelSurface = $get('parcel_surface') ?? 0;
        if ($surfaceApplied > $parcelSurface) {
            $set('surface_warning', 'La superficie aplicada excede la superficie del cuartel.');
        } else {
            $set('surface_warning', null);
        }

        if ($parcelSurface > 0) {
            $percentage = ($surfaceApplied / $parcelSurface) * 100;
            $set('application_percentage', round($percentage, 2));
        } else {
            $set('application_percentage', null);
        }
    }

    protected function getTodayClimateData()
    {
        try {
            Log::info('Iniciando getTodayClimateData para Order ID: ' . $this->ownerRecord->id);

            // Obtener el tenant actual (Field)
            $tenant = Filament::getTenant();
            if (!$tenant) {
                Log::warning('No se encontró tenant actual');
                return null;
            }
            Log::debug('Tenant encontrado', ['tenant_id' => $tenant->id, 'tenant_name' => $tenant->name]);

            // Obtener la primera Zone del tenant
            $zone = Zone::where('field_id', $tenant->id)->first();
            if (!$zone) {
                Log::warning('No se encontró ninguna Zone para el tenant', ['tenant_id' => $tenant->id]);
                return null;
            }
            Log::debug('Zone encontrada', ['zone_id' => $zone->id, 'zone_name' => $zone->name]);

            // Obtener el Field asociado a la Zone
            $field = $zone->field;
            if (!$field) {
                Log::warning('No se encontró Field para la Zone', ['zone_id' => $zone->id]);
                return null;
            }
            Log::debug('Field encontrado', ['field_id' => $field->id, 'field_name' => $field->name]);

            // Definir el rango de tiempo para hoy
            $initTime = Carbon::today('America/Santiago')->startOfDay()->toIso8601String();
            $endTime = Carbon::today('America/Santiago')->endOfDay()->toIso8601String();
            Log::debug('Rango de tiempo definido', ['initTime' => $initTime, 'endTime' => $endTime]);

            // Consultar medidas de la zona
            $wiseconnService = app(\App\Services\WiseconnService::class);
            $measures = $wiseconnService->getZoneMeasures($field, $zone, $initTime, $endTime);

            // Inicializar valores por defecto
            $climateData = [
                'wind_speed' => null,
                'temperature' => null,
                'humidity' => null,
            ];

            // Procesar las medidas para obtener los valores más recientes
            foreach ($measures as $sensorType => $measureData) {
                if (empty($measureData)) {
                    Log::warning("Datos vacíos para sensorType: {$sensorType}");
                    continue;
                }

                $latestData = collect($measureData[0]['data'])->sortByDesc('time')->first();
                Log::debug("Procesando sensorType: {$sensorType}", [
                    'latestData' => $latestData,
                    'measureDataCount' => count($measureData[0]['data']),
                ]);

                if ($sensorType === 'Temperature' && $latestData) {
                    $climateData['temperature'] = $latestData['value'];
                } elseif ($sensorType === 'Humidity' && $latestData) {
                    $climateData['humidity'] = $latestData['value'];
                } elseif ($sensorType === 'Wind Velocity' && $latestData) {
                    $climateData['wind_speed'] = $latestData['value'];
                }
            }

            Log::info('Datos climáticos obtenidos', ['climateData' => $climateData]);
            return (object) $climateData;
        } catch (\Exception $e) {
            Log::error('Error en getTodayClimateData', [
                'order_id' => $this->ownerRecord->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            \Filament\Notifications\Notification::make()
                ->title('Error al obtener datos climáticos')
                ->body('No se pudieron cargar los datos de Wiseconn: ' . $e->getMessage())
                ->danger()
                ->send();
            return null;
        }
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
                    ->suffix(' l')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ','),
                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie aplicada')
                    ->suffix(' ha')
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
                    ->suffix(' °C')
                    ->numeric(decimalPlaces: 1, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('moisture')
                    ->label('Humedad')
                    ->suffix(' %')
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