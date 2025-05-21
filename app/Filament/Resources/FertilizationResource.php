<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FertilizationResource\Pages;
use App\Filament\Resources\FertilizationResource\RelationManagers;
use App\Models\Fertilization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FertilizationResource extends Resource
{
    protected static ?string $model = Fertilization::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('parcel_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('field_id')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('date'),
                Forms\Components\TextInput::make('time'),
                Forms\Components\TextInput::make('duration')
                    ->numeric(),
                Forms\Components\TextInput::make('quantity_m3')
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('created_by')
                    ->numeric(),
                Forms\Components\TextInput::make('updated_by')
                    ->numeric(),
                Forms\Components\TextInput::make('deleted_by')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('parcel.name')
                    ->label('Cuartel')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_solution')
                    ->label('Cantidad Solución')
                    ->sortable(),
                Tables\Columns\TextColumn::make('dilution_factor')
                    ->label('Factor de Dilución')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_product')
                    ->label('Cantidad Producto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->sortable()
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('created_by')   
                    ->label('Creado por')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->label('Actualizado por')
                    ->sortable()
                    ->searchable(),
                
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
            'index' => Pages\ListFertilizations::route('/'),
            'create' => Pages\CreateFertilization::route('/create'),
            'edit' => Pages\EditFertilization::route('/{record}/edit'),
        ];
    }
}
