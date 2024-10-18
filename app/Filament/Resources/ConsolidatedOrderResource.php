<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsolidatedOrderResource\Pages;
use App\Filament\Resources\ConsolidatedOrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ConsolidatedOrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'carbon-report';
    protected static ?string $navigationLabel = 'Registro de aplicaciones';
    protected static ?string $slug = 'consolidated-orders';
    protected static ?string $navigationGroup = 'Informes';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('orderNumber')
                    ->label('Número de Orden')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Encargado')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('wharehouse.name')
                    ->label('Bodega de Preparación')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),



            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
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
            'index' => Pages\ListConsolidatedOrders::route('/'),
            'view' => Pages\ViewConsolidatedOrder::route('/{record}'),
        ];
    }
}
