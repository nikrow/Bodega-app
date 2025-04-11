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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
{
    return parent::getEloquentQuery()->with(['user.assignedTractors', 'user.assignedMachineries']);
}
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
                    ->required()
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('tractors')
                    ->label('Tractores Asignados')
                    ->multiple()
                    ->options(Tractor::pluck('name', 'id')->toArray())
                    ->reactive(),
                Forms\Components\Select::make('machineries')
                    ->label('Equipos Asignados')
                    ->multiple()
                    ->options(Machinery::pluck('name', 'id')->toArray())
                    ->reactive(),
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Operario'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Correo'),
                Tables\Columns\TextColumn::make('user.assignedTractors.name')
                    ->label('Tractores Asignados')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $tractors = $record->user->assignedTractors->pluck('name')->toArray();
                        Log::info('Tractores para OperatorAssignment', [
                            'user_id' => $record->user_id,
                            'tractors' => $tractors
                        ]);
                        return $tractors ?: ['Sin tractores asignados'];
                    }),
                Tables\Columns\TextColumn::make('user.assignedMachineries.name')
                    ->label('Equipos Asignados')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $machineries = $record->user->assignedMachineries->pluck('name')->toArray();
                        Log::info('Maquinarias para OperatorAssignment', [
                            'user_id' => $record->user_id,
                            'machineries' => $machineries
                        ]);
                        return $machineries ?: ['Sin equipos asignados'];
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperatorAssignments::route('/'),
            'edit' => Pages\EditOperatorAssignment::route('/{record}/edit'),
        ];
    }
}