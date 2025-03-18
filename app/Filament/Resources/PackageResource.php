<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Package;
use Filament\Forms\Form;
use App\Enums\Destination;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PackageResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\PackageResource\RelationManagers;
use OwenIt\Auditing\Contracts\Audit;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Anexos';
    protected static ?string $navigationLabel = 'Envases';
    protected static ?string $modelLabel = 'Envase';
    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Nombre del Envase
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                // Descripción del Envase
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->maxLength(1000),

                // Capacidad del Envase
                Forms\Components\TextInput::make('capacity')
                    ->label('Capacidad (lt/kg)')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->placeholder('Ingrese la capacidad del envase'),

                // Peso del Envase
                Forms\Components\TextInput::make('weight')
                    ->label('Peso')
                    ->numeric()
                    ->suffix('  kg')
                    ->minValue(0.001)
                    ->placeholder('Ingrese el peso del envase vacío'),

                // Destino del Envase (Enum)
                Forms\Components\Select::make('destination')
                    ->label('Destino')
                    ->options(Destination::options())
                    ->searchable()
                    ->required()
                    ->default(Destination::OTROS->value),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ID
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                // Nombre
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                // Descripción
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->limit(50)
                    ->sortable()
                    ->searchable(),

                // Capacidad
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacidad')
                    ->numeric()
                    ->suffix('  lt/kg')
                    ->sortable(),
                
                // Peso
                Tables\Columns\TextColumn::make('weight')
                    ->label('Peso')
                    ->sortable()
                    ->suffix('  kg')
                    ->numeric(),

                // Destino
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destino')
                    ->badge()
                    ->colors([
                        'success' => Destination::RECICLAJE_CAMPO_LIMPIO->value,
                        'warning' => Destination::RECICLAJE_VIVE_VERDE->value,
                        'primary' => Destination::RAICES->value,
                        'secondary' => Destination::PROVEEDOR->value,
                        'danger' => Destination::OTROS->value,
                    ]),
                
                // Fecha de Creación
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Puedes agregar filtros aquí si lo deseas
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}