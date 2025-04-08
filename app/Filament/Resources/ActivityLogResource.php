<?php

namespace App\Filament\Resources;

use App\Enums\RoleType;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Spatie\Activitylog\Models\Activity;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\ActivityLogResource\Pages;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'healthicons-o-security-worker';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $pluralLabel = 'Activity Logs';

    protected static ?string $navigationGroup = 'Admin';

    public static function table(Table $table): Table
    {
        return $table
            ->query(Activity::query())  
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
            
                TextColumn::make('description')
                    ->label('Descripción')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                    })
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->default('N/A'),
                TextColumn::make('subject_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('event')
                    ->label('Evento')
                    ->sortable()
                    ->searchable(),
                    TextColumn::make('properties')
                    ->label('Cambios')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'N/A';
                        }
                
                        $old = $state['old'] ?? [];
                        $new = $state['attributes'] ?? [];
                
                        $changes = [];
                        foreach ($old as $key => $value) {
                            $changes[] = "{$key}: {$value} → {$new[$key]}";
                        }
                
                        return implode(', ', $changes);
                    })
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->native(false)
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ])
                    ->label('Event'),
                SelectFilter::make('subject_type')
                    ->native(false)
                    ->label('Modelo')
                    ->options(function () {
                        // Obtiene los valores únicos de subject_type directamente desde la base de datos
                        $subjectTypes = Activity::select('subject_type')
                            ->distinct()
                            ->pluck('subject_type')
                            ->filter()
                            ->mapWithKeys(function ($subjectType) {
                                // Usa class_basename para mostrar solo el nombre del modelo (por ejemplo, "User")
                                $modelName = class_basename($subjectType);
                                return [$subjectType => $modelName];
                            })
                            ->toArray();

                        return $subjectTypes;
                    })
                    ->searchable(),
                SelectFilter::make('causer')
                    ->label('User')
                    ->native(false)
                    ->searchable()
                    ->options(fn () => \App\Models\User::pluck('name', 'id')->toArray())
                    ->attribute('causer_id'),
                    ], layout: FiltersLayout::AboveContent)
                    ->filtersFormColumns(3)
            ->actions([
                // Puedes añadir acciones como ver detalles si lo deseas
            ])
            ->bulkActions([
                
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}