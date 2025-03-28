<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Crop;
use App\Models\Work;
use Filament\Tables;
use App\Models\Report;
use App\Models\Tractor;
use Filament\Forms\Form;
use App\Models\Machinery;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ReportResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ReportResource\RelationManagers;
use Google\Service\Area120Tables\Resource\Tables as ResourceTables;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'carbon-report';

    protected static ?string $navigationGroup = 'Maquinaria';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Report';

    protected static ?string $modelLabel = 'Report';

    protected static ?string $pluralModelLabel = 'Reports';

    protected static ?string $slug = 'reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('crop_id')
                    ->label('Cultivo')
                    ->options(Crop::pluck('especie', 'id')->toArray())
                    ->required(),
                Forms\Components\Select::make('tractor_id')
                    ->label('Tractor')
                    ->options(Tractor::pluck('name', 'id')->toArray())
                    ->required(),
                Forms\Components\Select::make('machinery_id')
                    ->label('Maquinaria')
                    ->options(Machinery::pluck('name', 'id')->toArray())
                    ->required(),
                Forms\Components\Select::make('work_id')
                    ->label('Labor')
                    ->options(Work::pluck('name', 'id')->toArray())
                    ->required(),
                Forms\Components\TextInput::make('hourometer')
                    ->label('Horometro Inicio')
                    ->numeric(10, 2)
                    ->required(),
                Forms\Components\TextInput::make('observations')
                    ->label('Observaciones')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->label('Fecha'),
                    Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por'),
                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo'),
                Tables\Columns\TextColumn::make('tractor.name')
                    ->label('Tractor'),
                Tables\Columns\TextColumn::make('machinery.name')
                    ->label('Maquinaria'),
                Tables\Columns\TextColumn::make('work.name')  
                    ->label('Labor'),
                Tables\Columns\TextColumn::make('hourometer')
                    ->label('Horometro Inicio'),
                Tables\Columns\TextColumn::make('observations')
                    ->label('Observaciones'),
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
