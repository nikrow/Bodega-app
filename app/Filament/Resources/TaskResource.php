<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Crop;
use App\Models\Task;
use Filament\Tables;
use App\Enums\WorkType;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Anexos';
    protected static ?string $modelLabel = 'Labor';
    protected static ?string $pluralModelLabel = 'Labores';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Select::make('crop_id')
                ->label('Cultivo')
                ->relationship('crop','especie')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('work_type')
                ->label('Tipo de trabajo')
                ->required()
                ->reactive()
                ->options([
                    WorkType::JORNADA->value => 'Jornada',
                    WorkType::UNITARIA->value => 'Unitaria',
                ])
                ->native(false),
            Select::make('unit_type')
                ->label('Unidad')
                ->options([
                    'plantas' => 'plantas',
                    'kilos' => 'kilos',
                    'hileras' => 'hileras',
                    'hectareas' => 'hectareas',
                    'unidades' => 'unidades',
                ])
                ->visible(fn (Forms\Get $get) => $get('work_type') === WorkType::UNITARIA->value),
            Section::make('Opciones')
                ->collapsible(true)
                ->columns(2)
                ->schema([
                    Toggle::make('plant_control')
                        ->label('Control de planta')
                        ->default(false),
                    Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('crop.especie')
                ->label('Cultivo')
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('work_type')
                ->label('Tipo')
                ->formatStateUsing(fn ($state) => $state === WorkType::JORNADA ? 'Jornada' : 'Unitaria')
                ->badge()
                ->color(fn ($state) => $state === WorkType::JORNADA ? 'info' : 'warning'),
            Tables\Columns\TextColumn::make('unit_type')
                ->label('Unidad')
                ->toggleable(),
            Tables\Columns\IconColumn::make('plant_control')
                ->label('Control planta')
                ->toggleable(isToggledHiddenByDefault: true)
                ->boolean(),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Activo')
                ->toggleable(isToggledHiddenByDefault: true)
                ->boolean(),
        ])->defaultSort('name')
          ->actions([
              Tables\Actions\EditAction::make(),
              Tables\Actions\DeleteAction::make(),
              Tables\Actions\RestoreAction::make()
                ->visible(fn ($record) => $record->trashed()),
              Tables\Actions\ForceDeleteAction::make()
                ->visible(fn ($record) => $record->trashed()),
          ])
            ->filters([
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Cultivo')
                    ->options(Crop::all()->pluck('especie', 'id')),
                Tables\Filters\SelectFilter::make('work_type')
                    ->label('Tipo de trabajo')
                    ->options([
                        WorkType::JORNADA->value => 'Jornada',
                        WorkType::UNITARIA->value => 'Unitaria',
                    ]),
            ], layout: FiltersLayout::AboveContent)
          ->bulkActions([
              
          ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TaskResource\Pages\ListTasks::route('/'),
            'create' => TaskResource\Pages\CreateTask::route('/create'),
            'edit' => TaskResource\Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
