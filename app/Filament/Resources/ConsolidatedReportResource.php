<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\ConsolidatedReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\ConsolidatedReportResource\Pages;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class ConsolidatedReportResource extends Resource
{
    protected static ?string $model = ConsolidatedReport::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Maquinaria';
    protected static ?string $navigationLabel = 'Reports consolidados';
    protected static ?string $slug = 'reports-consolidados';
    protected static ?string $modelLabel = 'Reporte consolidado';
    protected static ?string $pluralModelLabel = 'Reportes consolidados';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->join('reports', 'consolidated_reports.report_id', '=', 'reports.id')
            ->select('consolidated_reports.*')
            ->with([
                'report.work',
                'report.field',
                'report.operator',
                'report.approvedBy',
                'machinery',
                'tractor'
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('reports.id', 'desc')
            ->defaultPaginationPageOption('10')
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->label('Proveedor')
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery->provider 
                            : $record->tractor->provider;
                    }),
                Tables\Columns\TextColumn::make('report.id')
                    ->label('ID Report')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->report ? $record->report->id : 'Sin reporte';
                    }),
                Tables\Columns\TextColumn::make('report.date')
                    ->label('Fecha')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('report.field.name')
                    ->label('Campo'), 
                Tables\Columns\TextColumn::make('report.operator.name')
                    ->label('Operador'),
                Tables\Columns\TextColumn::make('equipment')
                    ->label('Equipo')
                    ->limit(30)
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery->name 
                            : $record->tractor->name;
                    }),
                Tables\Columns\TextColumn::make('report.initial_hourometer')
                    ->label('Horómetro inicial')
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 1, ',', '.');
                    }),
                Tables\Columns\TextColumn::make('report.hourometer')
                    ->label('Horómetro final')
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 1, ',', '.');
                    }),
                Tables\Columns\TextColumn::make('hours')
                    ->label('Horas')
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery_hours 
                            : $record->tractor_hours;
                    }),
                Tables\Columns\TextColumn::make('report.work.name')
                    ->label('Labores'),
                Tables\Columns\TextColumn::make('report.work.cost_type')
                    ->label('Centro de Costo'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery->price 
                            : $record->tractor->price;
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery_total 
                            : $record->tractor_total;
                    }),
                Tables\Columns\TextColumn::make('report.approvedBy.name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Aprobado por'),    
                Tables\Columns\TextColumn::make('generated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Generado'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportExcel')
                    ->label('Exportar')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->maxDate(now()->subDays(30)) // Limita fecha inicial a 30 días atrás
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $endDate = $get('end_date');
                                if ($state && $endDate) {
                                    $daysDifference = Carbon::parse($state)->diffInDays(Carbon::parse($endDate));
                                    if ($daysDifference > 30) {
                                        Notification::make()
                                            ->title('Rango de fechas no permitido')
                                            ->body('El rango de fechas no puede superar los 30 días.')
                                            ->danger()
                                            ->send();
                                        $set('start_date', null); // Resetea la fecha inicial
                                    }
                                }
                            }),
                        DatePicker::make('end_date')
                            ->label('Fecha Fin')
                            ->default(now())
                            ->maxDate(now()) // Limita fecha final a hoy
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $startDate = $get('start_date');
                                if ($startDate && $state) {
                                    $daysDifference = Carbon::parse($startDate)->diffInDays(Carbon::parse($state));
                                    if ($daysDifference > 30) {
                                        Notification::make()
                                            ->title('Rango de fechas no permitido')
                                            ->body('El rango de fechas no puede superar los 30 días.')
                                            ->danger()
                                            ->send();
                                        $set('end_date', null); // Resetea la fecha final
                                    }
                                }
                            }),
                    ])
                    ->action(function (array $data) {
                        // Validación adicional antes de redirigir
                        $startDate = Carbon::parse($data['start_date']);
                        $endDate = Carbon::parse($data['end_date']);
                        if ($startDate->diffInDays($endDate) > 30) {
                            Notification::make()
                                ->title('Error')
                                ->body('El rango de fechas no puede superar los 30 días.')
                                ->danger()
                                ->send();
                            return;
                        }
                        return redirect()->route('consolidated-reports.export', [
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                        ]);
                    })
                    ->color('primary')
                    ->icon('bi-download'),
            ])
            ->filters([
                Tables\Filters\Filter::make('fecha')
                    ->columns(2)
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->maxDate(now()->subDays(30)) // Limita la fecha inicial a 30 días atrás
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $endDate = $get('end_date');
                                if ($state && $endDate) {
                                    $daysDifference = Carbon::parse($state)->diffInDays(Carbon::parse($endDate));
                                    if ($daysDifference > 30) {
                                        Notification::make()
                                            ->title('Rango de fechas no permitido')
                                            ->body('El rango de fechas no puede superar los 30 días.')
                                            ->danger()
                                            ->send();
                                        $set('start_date', null); // Resetea la fecha inicial
                                    }
                                }
                            }),
                        DatePicker::make('end_date')
                            ->label('Fecha Fin')
                            ->default(now())
                            ->maxDate(now()) // Limita la fecha final a hoy
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $startDate = $get('start_date');
                                if ($startDate && $state) {
                                    $daysDifference = Carbon::parse($startDate)->diffInDays(Carbon::parse($state));
                                    if ($daysDifference > 30) {
                                        Notification::make()
                                            ->title('Rango de fechas no permitido')
                                            ->body('El rango de fechas no puede superar los 30 días.')
                                            ->danger()
                                            ->send();
                                        $set('end_date', null); // Resetea la fecha final
                                    }
                                }
                            }),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn ($q) => $q->whereDate('reports.date', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->whereDate('reports.date', '<=', $data['end_date']));
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                // Acciones individuales (sin cambios)
            ])
            ->bulkActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsolidatedReports::route('/'),
        ];
    }
}