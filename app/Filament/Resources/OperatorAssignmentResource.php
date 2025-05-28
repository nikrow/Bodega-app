<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleType;
use App\Models\Tractor;
use Filament\Forms\Form;
use App\Models\Machinery;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\OperatorAssignment;
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\OperatorAssignmentResource\Pages;
use App\Filament\Resources\OperatorAssignmentResource\RelationManagers\ReportsRelationManager;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class OperatorAssignmentResource extends Resource
{
    protected static ?string $model = OperatorAssignment::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Maquinaria';
    protected static ?string $navigationLabel = 'Asignación de Operarios';
    protected static ?string $modelLabel = 'Asignación de Operario';
    protected static ?string $pluralModelLabel = 'Asignaciones de Operarios';
    protected static ?string $slug = 'operator-assignments';

    
    public static function canCreate(): bool
    {
        return false;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Operario')
                    ->options(User::where('role', RoleType::OPERARIO->value)->pluck('name', 'id'))
                    ->disabled()
                    ->required()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record && $record->user_id) {
                            $component->state($record->user_id);
                        }
                    }),

                Forms\Components\Select::make('fields')
                    ->label('Campos')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(\App\Models\Field::pluck('name', 'id'))
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record && $record->user) {
                            $component->state($record->user->fields->pluck('id')->toArray());
                        }
                    }),


                Forms\Components\Select::make('tractors')
                    ->label('Tractores')
                    ->options(Tractor::pluck('name', 'id'))
                    ->multiple()
                    ->preload()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record && $record->user) {
                            // Cargar los IDs de los tractores asignados al usuario
                            $component->state($record->user->assignedTractors->pluck('id')->toArray());
                        }
                    }),

                Forms\Components\Select::make('machineries')
                    ->label('Maquinarias')
                    ->options(Machinery::pluck('name', 'id'))
                    ->multiple()
                    ->preload()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record && $record->user) {
                            // Cargar los IDs de las maquinarias asignadas al usuario
                            $component->state($record->user->assignedMachineries->pluck('id')->toArray());
                        }
                    }),
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Operario'),
                
                Tables\Columns\TextColumn::make('user.fields.name')
                    ->label('Campos Asignados')
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if (!$record->user) return ['Sin operario asignado'];
                        $fields = $record->user->fields->pluck('name')->toArray();
                        return $fields ?: ['Sin campos asignados'];
                    }),
                
                Tables\Columns\TextColumn::make('user.assignedTractors.name')
                    ->label('Tractores Asignados')
                    ->badge()
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if (!$record->user) return ['Sin operario asignado'];
                        $tractors = $record->user->assignedTractors->pluck('name')->toArray();
                        return $tractors ?: ['Sin tractores asignados'];
                    }),

                Tables\Columns\TextColumn::make('user.assignedMachineries.name')
                    ->label('Implementos Asignados')
                    ->badge()
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if (!$record->user) return ['Sin operario asignado'];
                        $machineries = $record->user->assignedMachineries->pluck('name')->toArray();
                        return $machineries ?: ['Sin implementos asignados'];
                    }),
                Tables\Columns\TextColumn::make('last_day_hours')
                    ->label('Horas día anterior')
                    ->badge()
                    ->getStateUsing(function (OperatorAssignment $record): string {
                        $totalHours = $record->reports()
                            ->whereDate('date', now()->subDay())
                            ->sum('hours');
                        return number_format($totalHours, 2);
                    }),
                Tables\Columns\TextColumn::make('current_month_hours')
                    ->label('Horas Mes Actual')
                    ->badge()
                    ->getStateUsing(function (OperatorAssignment $record): string {
                        $totalHours = $record->reports()
                            ->whereBetween('date', [
                                now()->startOfMonth(),
                                now()->endOfMonth(),
                            ])
                            ->sum('hours');
                        return number_format($totalHours, 2);
                    }),
            ])
            ->filters([
                //
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
    
    public static function getRelations(): array
    {
        return [
            ReportsRelationManager::class,
            AuditsRelationManager::class
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperatorAssignments::route('/'),
            'edit' => Pages\EditOperatorAssignment::route('/{record}/edit'),
        ];
    }
}