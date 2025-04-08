<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TractorResource\Pages;
use App\Filament\Resources\TractorResource\RelationManagers;
use App\Models\Tractor;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TractorResource extends Resource
{
    protected static ?string $model = Tractor::class;

    protected static ?string $navigationIcon = 'phosphor-tractor-light';

    protected static ?string $navigationGroup = 'Maquinaria';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Maquina';

    protected static ?string $modelLabel = 'Maquina';

    protected static ?string $pluralModelLabel = 'Máquinas';

    protected static ?string $slug = 'maquinas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Forms\Components\Select::make('provider')
                    ->label('Proveedor')
                    ->native(false)
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
                Forms\Components\TextInput::make('qrcode')
                    ->label('Código QR')
                    ->disabled(),
                Forms\Components\TextInput::make('hourometer')
                    ->label('Horómetro actual')
                    ->numeric(2, 2)
                    ->disabled(fn($record) => $record !== null) 
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
                Tables\Columns\TextColumn::make('qrcode')
                    ->label('Código QR')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Actualizado por')
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
            'index' => Pages\ListTractors::route('/'),
            'create' => Pages\CreateTractor::route('/create'),
            'edit' => Pages\EditTractor::route('/{record}/edit'),
        ];
    }
}
