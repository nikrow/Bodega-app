<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperatorResource\Pages;
use App\Filament\Resources\OperatorResource\RelationManagers;
use App\Models\Operator;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OperatorResource extends Resource
{
    protected static ?string $model = Operator::class;

    protected static ?string $navigationIcon = 'eos-dashboard';

    protected static ?string $navigationGroup = 'Anexos';

    protected static ?int $navigationSort = 80;

    protected static ?string $navigationLabel = 'Operador';

    protected static ?string $modelLabel = 'Oprerador';

    protected static ?string $pluralModelLabel = 'Operadores';

    protected static ?string $slug = 'operadores';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->required(),
                Forms\Components\TextInput::make('RUT')
                    ->label('RUT')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código'),
                Tables\Columns\TextColumn::make('RUT')
                    ->label('RUT'), 
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Actualizado por')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->searchable()
                    ->date('d/m/Y')
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
            'index' => Pages\ListOperators::route('/'),
            'create' => Pages\CreateOperator::route('/create'),
            'edit' => Pages\EditOperator::route('/{record}/edit'),
        ];
    }
}
