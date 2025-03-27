<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MachineryResource\Pages;
use App\Filament\Resources\MachineryResource\RelationManagers;
use App\Models\Machinery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MachineryResource extends Resource
{
    protected static ?string $model = Machinery::class;

    protected static ?string $navigationIcon = 'phosphor-crane';

    protected static ?string $navigationGroup = 'Maquinaria';

    protected static ?int $navigationSort = 70;

    protected static ?string $navigationLabel = 'Equipo';

    protected static ?string $modelLabel = 'Equipo';

    protected static ?string $pluralModelLabel = 'Equipos';

    protected static ?string $slug = 'equipo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Forms\Components\Select::make('provider')
                    ->label('Proveedor')
                    ->options([
                        'Bemat' => 'Bemat',
                        'TractorAmarillo' => 'Tractor Amarillo',
                        'Fedemaq' => 'Fedemaq',
                        'SchmditHermanos' => 'Schmdit Hermanos',
                        'MayolYPiraino' => 'Mayol y Piraino',
                        'Otro' => 'Otro',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('SapCode')
                    ->label('Código SAP')
                    ->unique(),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->numeric(2, 2)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('SapCode')
                    ->label('Código SAP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMachineries::route('/'),
            'create' => Pages\CreateMachinery::route('/create'),
            'edit' => Pages\EditMachinery::route('/{record}/edit'),
        ];
    }
}
