<?php

namespace App\Filament\Resources;

use App\Enums\ProviderType;
use Filament\Forms;
use Filament\Tables;
use App\Models\Tractor;
use Filament\Forms\Form;
use Filter\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Audit;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TractorResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\TractorResource\RelationManagers;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

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
    
    protected static bool $isScopedToTenant = false;

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
                    ->options(ProviderType::class)
                    ->required(),
                Forms\Components\TextInput::make('SapCode')
                    ->label('Código SAP')
                    ->rules(function (Forms\Get $get) {
                        return Rule::unique('tractors', 'SapCode')
                            ->ignore($get('id'));
                    })
                    ->required(),
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
                    ->disabled(fn($record) => $record !== null && !Auth::user()->isAdmin()) 
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
                Tables\Columns\TextColumn::make('hourometer')
                    ->label('Horómetro actual')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_month_hours')
                    ->label('Horas Mes Actual')
                    ->badge()
                    ->getStateUsing(function (Tractor $record): string {
                        $totalHours = $record->reports()
                            ->whereBetween('date', [
                                now()->startOfMonth(),
                                now()->endOfMonth(),
                            ])
                            ->sum('hours');
                        return number_format($totalHours, 2);
                    }),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Actualizado por')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Proveedor')
                    ->multiple()
                    ->options([
                        'Jorge Schmidt' => 'Jorge Schmidt',
                        'Bemat' => 'Bemat',
                        'TractorAmarillo' => 'Tractor Amarillo',
                        'Fedemaq' => 'Fedemaq',
                        'SchmditHermanos' => 'Schmdit Hermanos',
                        'MayolYPiraino' => 'Mayol y Piraino',
                        'Otro' => 'Otro',
                    ]),
            ])
        
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                    ExportBulkAction::make('export'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReportsRelationManager::class,
            AuditsRelationManager::class,
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
