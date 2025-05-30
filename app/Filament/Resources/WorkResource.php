<?php

namespace App\Filament\Resources;

use App\Enums\CostType;
use App\Filament\Resources\WorkResource\Pages;
use App\Filament\Resources\WorkResource\RelationManagers;
use App\Models\Work;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkResource extends Resource
{
    protected static ?string $model = Work::class;

    protected static ?string $navigationIcon = 'fas-list';

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationGroup = 'Maquinaria';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Labor';

    protected static ?string $modelLabel = 'Labor';

    protected static ?string $pluralModelLabel = 'Labores';

    protected static ?string $slug = 'labores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->nullable(),
                Forms\Components\Select::make('cost_type')
                    ->label('Centro Costo')
                    ->options(CostType::class)
                    ->native(false)
                    ->required(),
        
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('cost_type')
                    ->label('Centro Costo')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Descripción'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    
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
            'index' => Pages\ListWorks::route('/'),
            'create' => Pages\CreateWork::route('/create'),
            'edit' => Pages\EditWork::route('/{record}/edit'),
        ];
    }
}
