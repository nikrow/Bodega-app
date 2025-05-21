<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ImportBatch;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ImportBatchResource\Pages;
use App\Filament\Resources\ImportBatchResource\RelationManagers;
use App\Filament\Resources\ImportBatchResource\RelationManagers\EventsRelationManager;

class ImportBatchResource extends Resource
{
    protected static ?string $model = ImportBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Importaciones';
    protected static ?string $navigationLabel = 'Eventos Importados';
    protected static ?string $label = 'Evento Importado';
    protected static ?string $pluralLabel = 'Eventos Importados';
    protected static ?string $slug = 'imported-events';
    protected static bool $isScopedToTenant = false;
    protected static ?string $modelLabel = 'Evento Importado';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('field.name')
                    ->label('Campo'),
                TextColumn::make('import_date')
                    ->label('Fecha de Importación'),
                TextColumn::make('total_records')
                    ->label('Total Registros'),
                TextColumn::make('success_count')
                    ->label('Éxitos'),
                TextColumn::make('failed_count')
                    ->label('Fallos'),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportBatches::route('/'),
            'create' => Pages\CreateImportBatch::route('/create'),
            'edit' => Pages\EditImportBatch::route('/{record}/edit'),
        ];
    }
}
