<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchResource\Pages;
use App\Filament\Resources\BatchResource\RelationManagers;
use App\Models\Batch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Bodega';
    protected static ?string $navigationLabel = 'Lotes';
    protected static ?string $modelLabel = 'Lote';
    protected static ?int $navigationSort = 40;

/* 
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    } */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->searchable()
                    ->label('Fecha de ingreso')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lot_number')
                    ->searchable()
                    ->label('Lote')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.active_ingredients')
                    ->label('Ingrediente Activo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.sag_code')
                    ->searchable()
                    ->label('Código SAG')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->searchable()
                    ->label('Cantidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->searchable()
                    ->date('d/m/Y')
                    ->label('Fecha de Vencimiento')
                    ->sortable(),
                Tables\Columns\TextColumn::make('buy_order')
                    ->searchable()
                    ->label('Orden de Compra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->label('Número de guía')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->searchable()
                    ->label('Proveedor')
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
            'index' => Pages\ListBatches::route('/'),
            'create' => Pages\CreateBatch::route('/create'),
            'edit' => Pages\EditBatch::route('/{record}/edit'),
        ];
    }
}
