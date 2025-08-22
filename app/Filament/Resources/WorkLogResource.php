<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Task;
use App\Models\User;
use Filament\Tables;
use App\Models\Parcel;
use App\Enums\RoleType;
use App\Enums\WorkType;
use App\Models\WorkLog;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class WorkLogResource extends Resource
{
    protected static ?string $model = WorkLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Operación';
    protected static ?string $modelLabel = 'Registro de faena';
    protected static ?string $pluralModelLabel = 'Registros de faenas';

    public static function form(Form $form): Form
{
    
    return $form->schema([
        Section::make('Información')
            ->schema([
                DatePicker::make('date')
                    ->label('Fecha')
                    ->default(today())
                    ->required(),

                Select::make('crop_id')
                    ->label('Cultivo')
                    ->relationship('crop', 'especie')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),

                Select::make('responsible_id')
                    ->label('Responsable de campo')
                    ->options(function () {
                        return User::query()
                            ->where('role', RoleType::SUPERVISOR->value)
                            ->whereHas('fields', fn ($query) =>
                                $query->where('fields.id', Filament::getTenant()->id)
                            )
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->default(fn () => \Filament\Facades\Filament::auth()->user()?->role === \App\Enums\RoleType::SUPERVISOR
                        ? \Filament\Facades\Filament::auth()->id()
                        : null)
                    ->disabled(fn () => \Filament\Facades\Filament::auth()->user()?->role === \App\Enums\RoleType::SUPERVISOR)
                    ->dehydrated(true)
                    ->searchable()
                    ->preload() 
                    ->required(),

                Select::make('contractor_id')
                    ->label('Contratista')
                    ->relationship('contractor', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->columns(2),

        Section::make('Detalles de la faena')
            ->schema([
                Repeater::make('workLogs')
                    ->label('Detalles de faenas')
                    ->minItems(1)
                    ->cloneable()
                    ->columns(5)
                    ->addActionLabel('Agregar otra faena')
                    ->schema([
                        Select::make('parcel_id')
                            ->label('Cuartel')
                            ->options(function (Get $get) {
                                $tenantId = Filament::getTenant()->id;
                                $cropId   = $get('../../crop_id');
                                if ($cropId) {
                                    return Parcel::where('crop_id', $cropId)
                                        ->where('field_id', $tenantId)
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }
                                return [];
                            })
                            ->searchable()
                            ->placeholder('Cuarteles')
                            ->preload()
                            ->live()
                            ->required(),

                        Select::make('task_id')
                            ->label('Faena')
                            ->options(function (Get $get) {
                                $cropId = $get('../../crop_id');
                                return Task::query()
                                    ->where('is_active', true)
                                    ->when($cropId, fn ($q) => $q->where('crop_id', $cropId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('Faenas')
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('unit_type', Task::find($state)?->unit_type);
                            }),

                        TextInput::make('people_count')
                            ->label('Personas')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->minValue(0.1)
                            ->rule('decimal:0,1')
                            ->required(fn (Get $get) => self::taskIs($get('task_id'), WorkType::UNITARIA))
                            ->disabled(fn (Get $get) => self::taskIs($get('task_id'), WorkType::JORNADA)),

                        TextInput::make('unit_type')
                            ->label('Unidad')
                            ->disabled() 
                            ->dehydrated(true)
                            ->disabled(fn (Get $get) => self::taskIs($get('task_id'), WorkType::JORNADA))
                            ->afterStateHydrated(function ($state, callable $set, Get $get) {
                                if (blank($state) && $task = self::findTask($get('task_id'))) {
                                    $set('unit_type', $task->unit_type);
                                }
                            }),
                        Toggle::make('observations')
                            ->label('¿Agregar observaciones?')
                            ->live()
                            ->default(false),
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->hidden(fn (Get $get) => !$get('observations'))
                            ->columnSpanFull(),

                        Hidden::make('by_jornada'),
                        Hidden::make('by_unit'),
                    ]),
            ]),
    ]);
}

    protected static function findTask($id): ?Task
    {
        if (empty($id)) return null;
        static $cache = [];
        return $cache[$id] ??= Task::query()->select('id', 'work_type', 'unit_type')->find($id);
    }

    protected static function taskIs($taskId, WorkType $type): bool
    {
        $task = self::findTask($taskId);
        return $task?->work_type === $type;
    }

   private static function onlyAdmin(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();

        return $user instanceof \App\Models\User
            ? $user->isAdmin()
            : false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d-m-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('parcel.name')
                    ->label('Parcela')
                    ->searchable(),

                Tables\Columns\TextColumn::make('task.name')
                    ->label('Labor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('responsible.name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Responsable'),

                Tables\Columns\TextColumn::make('contractor.name')
                    ->label('Contratista')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('people_count')
                    ->label('Personas')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric(decimalPlaces: 1)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('unit_type')
                    ->label('Unidad')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_completed')
                    ->label('Revisado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime('d-m-Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),   
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Actualizado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn () => self::onlyAdmin()),
                Tables\Filters\SelectFilter::make('is_completed')
                    ->label('Revisado')
                    ->default(0)
                    ->options([
                        1 => 'Completado',
                        0 => 'Pendiente',
                    ]),
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Cultivo')
                    ->options(\App\Models\Crop::all()->pluck('especie', 'id')),
                Tables\Filters\SelectFilter::make('task_id')
                    ->label('Labor')
                    ->options(\App\Models\Task::all()->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('responsible_id')
                    ->label('Responsable')
                    ->options(function () {
                        return User::query()
                            ->where('role', RoleType::SUPERVISOR->value)
                            ->whereHas('fields', fn ($query) =>
                                $query->where('fields.id', Filament::getTenant()->id)
                            )
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    }),
                Tables\Filters\SelectFilter::make('contractor_id')
                    ->label('Contratista')
                    ->options(\App\Models\Contractor::all()->pluck('name', 'id')),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros'),
            )
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->visible(fn (WorkLog $record) => !$record->is_completed),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (WorkLog $record) => !$record->is_completed),
                    Tables\Actions\RestoreAction::make()
                        ->visible(fn ($record) => $record->trashed()),
                    Tables\Actions\Action::make('complete')
                        ->label('Cerrar faena')
                        ->action(fn (WorkLog $record) => $record->update(['is_completed' => true]))
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->visible()
                        ->visible(fn (WorkLog $record) => !$record->is_completed)
                        ->color('success'),
                ])
                    ->button()
                    ->size(ActionSize::Small)
            ])
            ->bulkActions([
                ExportBulkAction::make('export')
                    ->label('Exportar'),
                Tables\Actions\BulkAction::make('complete')
                    ->label('Cerrar faenas')
                    ->action(function (Tables\Actions\BulkAction $action, \Illuminate\Database\Eloquent\Collection $records) {
                        $toUpdate = $records->filter(fn (WorkLog $r) => ! $r->is_completed);

                        if ($toUpdate->isEmpty()) {
                            $action->failure();
                            $action->failureNotificationTitle('Nada para cerrar');
                            return;
                        }

                        \App\Models\WorkLog::whereIn('id', $toUpdate->pluck('id'))->update(['is_completed' => true]);

                        $skipped = $records->count() - $toUpdate->count();
                        $action->success();
                        $action->successNotificationTitle(
                            'Cerradas ' . $toUpdate->count() . ' faenas' . ($skipped ? " ({$skipped} ya estaban cerradas)" : '')
                        );
                    })
                    ->deselectRecordsAfterCompletion()
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->color('success'),
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function (Tables\Actions\BulkAction $action, \Illuminate\Database\Eloquent\Collection $records) {
                        $deletables = $records->filter(fn (WorkLog $r) => ! $r->is_completed);

                        if ($deletables->isEmpty()) {
                            $action->failure();
                            $action->failureNotificationTitle('Nada para eliminar');
                            return;
                        }

                        \App\Models\WorkLog::whereIn('id', $deletables->pluck('id'))->delete();

                        $skipped = $records->count() - $deletables->count();
                        $action->success();
                        $action->successNotificationTitle(
                            'Eliminadas ' . $deletables->count() . ' faenas' . ($skipped ? " ({$skipped} no se podían eliminar)" : '')
                        );
                    }),
                ]);
    }



    public static function getPages(): array
    {
        return [
            'index'  => WorkLogResource\Pages\ListWorkLogs::route('/'),
            'create' => WorkLogResource\Pages\CreateWorkLog::route('/create'),
            'edit'   => WorkLogResource\Pages\EditWorkLog::route('/{record}/edit'),
        ];
    }
}
