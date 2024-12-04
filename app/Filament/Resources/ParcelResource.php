<?php

namespace App\Filament\Resources;



use App\Filament\Resources\ParcelResource\Pages;
use App\Models\Crop;
use App\Models\Parcel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class ParcelResource extends Resource
{
    protected static ?string $model = Parcel::class;

    protected static ?string $navigationIcon = 'eos-dashboard';

    protected static ?string $navigationGroup = 'Anexos';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Cuartel';

    protected static ?string $modelLabel = 'Cuartel';

    protected static ?string $pluralModelLabel = 'Cuarteles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'cuarteles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->rules(function (Forms\Get $get) {
                        return Rule::unique('parcels', 'name')
                            ->ignore($get('id'));
                    }),
                Forms\Components\Select::make('crop_id')
                    ->label('Cultivo')
                    ->options(Crop::all()->pluck('especie', 'id')->toArray())
                    ->required(),
                Forms\Components\Select::make('planting_year')
                    ->label('Año Plantación')
                    ->integer()
                    ->searchable(true)
                    ->required(),
                Forms\Components\TextInput::make('plants')
                    ->label('Plantas')
                    ->integer()
                    ->required(),
                Forms\Components\TextInput::make('surface')
                    ->label('Superficie')
                    ->suffix('ha')
                    ->required()
                    ->numeric(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('planting_year')
                    ->label('Año de Plantación')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plants')
                    ->label('Plantas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie')
                    ->searchable()
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Cultivo')
                    ->searchable(true)
                    ->options(Crop::all()->pluck('especie', 'id')->toArray()),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParcels::route('/'),
            'create' => Pages\CreateParcel::route('/create'),
            'edit' => Pages\EditParcel::route('/{record}/edit'),
        ];
    }
}
