<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Provider;
use Filament\Forms\Form;
use App\Enums\StatusType;
use Filament\Tables\Table;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use App\Models\PurchaseOrderDetail;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers\PurchaseOrderDetailsRelationManager;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'fas-sheet-plastic';
    protected static ?string $navigationGroup = 'Bodega';

    protected static ?string $slug = 'oc';
    protected static ?string $pluralModelLabel = 'Ordenes de compra';
    protected static ?string $modelLabel = 'Orden de Compra';
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('number')
                    ->label('Número de orden')
                    ->integer()
                    ->rules(function (Forms\Get $get) {
                        return Rule::unique('purchase_orders', 'number')
                            ->ignore($get('id'));
                    })
                    ->required(),

                Forms\Components\Select::make('provider_id')
                    ->label('Proveedor')
                    ->preload()
                    ->options(
                        Provider::all()->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable(),

                Forms\Components\DatePicker::make('date')
                    ->label('Fecha creación')
                    ->default(now())
                    ->required(),
                Forms\Components\ToggleButtons::make('status')
                    ->label('Estado')
                    ->inline()
                    ->grouped()
                    ->hidden(fn($record) => $record == null)
                    ->options(StatusType::class)
                    ->disableOptionWhen(fn($record) => $record !== null),
                    
                Forms\Components\Textarea::make('observation')
                    ->label('Observación')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('N° Orden')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha creación')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('observation')
                    ->label('Observación')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
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
            PurchaseOrderDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
