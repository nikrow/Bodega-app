<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\User;
use App\Models\Tractor;
use App\Models\Machinery;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\OperatorAssignmentResource\Pages;

class OperatorAssignmentResource extends Resource
{
    protected static ?string $model = User::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Maquinaria';
    protected static ?string $navigationLabel = 'Asignación de Operarios';
    protected static ?string $modelLabel = 'Operario';
    protected static ?string $pluralModelLabel = 'Operarios';
    protected static ?string $slug = 'operator-assignments';

    // Filtrar solo operarios
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('role', 'operator');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Operario')
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\Hidden::make('role')
                    ->default('operator')
                    ->dehydrated(true),
                Forms\Components\Select::make('tractor_id')
                    ->label('Tractor Asignado')
                    ->options(Tractor::whereNull('operator_id')->pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $record) {
                        if ($state && $record) {
                            Tractor::where('id', $state)->update(['operator_id' => $record->id]);
                        }
                    })
                    ->nullable(),
                Forms\Components\Select::make('machineries')
                    ->label('Equipos Asignados')
                    ->multiple()
                    ->options(Machinery::pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(function ($state, $record) {
                        if ($record) {
                            $record->assignedMachineries()->sync($state);
                        }
                    })
                    ->default(fn ($record) => $record ? $record->assignedMachineries()->pluck('machinery_id')->toArray() : []),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Operario'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo'),
                Tables\Columns\TextColumn::make('assignedTractor.name')
                    ->label('Tractor Asignado'),
                Tables\Columns\TextColumn::make('assignedMachineries.name')
                    ->label('Equipos Asignados')
                    ->formatStateUsing(fn ($state) => $state->pluck('name')->implode(', ')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        
                        Tractor::where('operator_id', $record->id)->update(['operator_id' => null]);
                        $record->assignedMachineries()->detach();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                Tractor::where('operator_id', $record->id)->update(['operator_id' => null]);
                                $record->assignedMachineries()->detach();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperatorAssignments::route('/'),
            'create' => Pages\CreateOperatorAssignment::route('/create'),
            'edit' => Pages\EditOperatorAssignment::route('/{record}/edit'),
        ];
    }
}