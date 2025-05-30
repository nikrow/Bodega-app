<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Forms;
use App\Models\User;
use App\Models\Work;
use Filament\Tables;
use App\Models\Report;
use App\Models\Tractor;
use Filament\Forms\Form;
use App\Models\Machinery;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\ReportResource\Pages;
use Google\Service\Forms\Resource\Forms as ResourceForms;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static ?string $navigationIcon = 'carbon-report';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'Report';
    protected static ?string $modelLabel = 'Report';
    protected static ?string $pluralModelLabel = 'Reports';
    protected static ?string $slug = 'reports';

    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
    $query = parent::getEloquentQuery()->with([
        'operator.assignedTractors',
        'operator.assignedMachineries', 
        'field',     
        'tractor',  
        'machinery', 
        'work',     
    ]);

    if (Auth::user()->isOperator()) {
        $query->where('operator_id', Auth::id());
    }

    return $query;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user(); 

        // Definimos el esquema base
        $schema = [
            Forms\Components\Select::make('field_id')
                ->label('Campo')
                ->options(function () {
                    return Filament::getTenant()->pluck('name', 'id')->toArray();
                })
                ->default(Filament::getTenant()->id)
                ->disabled(fn($record) => $record === null),
            Forms\Components\DatePicker::make('date')
                ->label('Fecha')
                ->default(now())
                ->required(),
            
            Forms\Components\Select::make('tractor_id')
                ->label('Máquina')
                ->native(false)
                ->options(function (callable $get) use ($user) {
                    if ($user->isOperator()) {
                        return $user->assignedTractors()->pluck('tractors.name', 'tractors.id')->toArray();
                    } else {
                        // Para otros roles, mostrar los tractores del operador seleccionado
                        $operatorId = $get('operator_id');
                        if ($operatorId) {
                            $operator = User::find($operatorId);
                            return $operator ? $operator->assignedTractors()->pluck('tractors.name', 'tractors.id')->toArray() : [];
                        }
                        return [];
                    }
                })
                ->required()
                ->preload()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $lastReport = Report::where('tractor_id', $state)
                    ->orderBy('id', 'desc')
                    ->first();
                $initialHourometer = $lastReport ? $lastReport->hourometer : Tractor::find($state)->hourometer ?? 0;
                $set('initial_hourometer', $initialHourometer);
                    $set('machinery_id', null);
                    $set('work_id', null);
                }),

            Forms\Components\Select::make('machinery_id')
                ->label('Implemento')
                ->native(false)
                ->options(function (callable $get) use ($user) {
                    if ($user->isOperator()) {
                        return $user->assignedMachineries()->pluck('machineries.name', 'machineries.id')->toArray();
                    }
                    else {
                        // Para otros roles, mostrar los equipos del operador seleccionado
                        $operatorId = $get('operator_id');
                        if ($operatorId) {
                            $operator = User::find($operatorId);
                            return $operator ? $operator->assignedMachineries()->pluck('machineries.name', 'machineries.id')->toArray() : [];
                        }
                        return [];
                    }
                })
                ->required()
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('work_id', null)),
            Forms\Components\Select::make('work_id')
                ->label('Labor')
                ->native(false)
                ->options(function (callable $get) {
                    $machineryId = $get('machinery_id');
                    if (!$machineryId) {
                        return Work::pluck('name', 'id')->toArray();
                    }
                    return Machinery::find($machineryId)?->works()->pluck('works.name', 'works.id')->toArray() ?? [];
                })
                ->required()
                ->reactive(),
            
            Forms\Components\TextInput::make('initial_hourometer')
                ->label('Horómetro Inicial')
                ->numeric()
                ->readOnly()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, $get) {
                    $final = (float) $get('hourometer') ?? 0;
                    $set('hours', $final > $state ? $final - $state : 0);
                }),

            Forms\Components\TextInput::make('hourometer')
                ->label('Horómetro Final')
                ->required()
                ->step(0.01)
                ->afterStateUpdated(function ($state, callable $set, $get) {
                    $state = str_replace(',', '.', $state);
                    $initial = (float) $get('initial_hourometer') ?? 0;
                    $hours = $state > $initial ? $state - $initial : 0;
                    $set('hours', $hours);
                    if ($hours == 0) {
                        $set('hours', null); 
                    }
                })
                ->formatStateUsing(function ($state) {
                    return str_replace(',', '.', $state);
                })
                ->dehydrateStateUsing(function ($state) {
                    return str_replace(',', '.', $state);
                })
                ->rules([
                    fn ($get) => function (string $attribute, $value, Closure $fail) use ($get) {
                        $value = str_replace(',', '.', $value);
                        $initial = (float) $get('initial_hourometer') ?? 0;
                        $tractorId = $get('tractor_id');
                        $isEditing = $get('id') !== null;

                        if ($value <= $initial) {
                            $fail("El horómetro final debe ser mayor que el inicial para registrar horas trabajadas.");
                        }

                        if ($tractorId && !$isEditing) {
                            $lastReport = Report::where('tractor_id', $tractorId)
                                ->orderBy('id', 'desc')
                                ->first();
                            $expectedInitial = $lastReport ? $lastReport->hourometer : Tractor::find($tractorId)->hourometer ?? 0;

                            if ($initial != $expectedInitial) {
                                $fail("El horómetro inicial no coincide con el último valor ingresado. Por favor, recargue la página.");
                            }

                            if ($value < $expectedInitial) {
                                $fail("El horómetro final no puede ser menor que el último horómetro ingresado ({$expectedInitial}).");
                            }
                        }

                        $maxAllowed = $initial + 15;
                        if ($value > $maxAllowed) {
                            $fail("El horómetro final ({$value}) no puede superar las 15 horas del inicial ({$initial}). Máximo: {$maxAllowed}");
                        }
                    },
                ]),

            Forms\Components\TextInput::make('hours')
                ->label('Horas Trabajadas')
                ->numeric()
                ->reactive()
                ->hidden()
                ->disabled()
                ->dehydrated(true),

            Forms\Components\Textarea::make('observations')
                ->label('Observaciones')
                ->nullable(),
        ];

        if ($user->isOperator()) {
            // Para operadores, el operator_id es oculto y se establece automáticamente
            array_unshift($schema, Forms\Components\Hidden::make('operator_id')->default($user->id));
        } else {
            // Para UsuarioMaquinaria y Admin, mostrar un select con todos los operadores
            array_unshift($schema, Forms\Components\Select::make('operator_id')
                ->label('Operador')
                ->options(
                    User::where('role', \App\Enums\RoleType::OPERARIO)->pluck('name', 'id')->toArray()
                )
                ->required()
                ->reactive()
            );
        }

    return $form->schema($schema);
        }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operador'),
                Tables\Columns\TextColumn::make('tractor.name')
                    ->limit(30)
                    ->label('Máquina'),
                Tables\Columns\TextColumn::make('machinery.name')
                    ->limit(30)
                    ->label('Equipo'),
                Tables\Columns\TextColumn::make('work.name')
                    ->label('Labor'),
                Tables\Columns\TextColumn::make('hours')
                    ->badge()
                    ->label('Horas'),
                Tables\Columns\TextColumn::make('initial_hourometer')
                    ->label('Horómetro Inicial'),
                Tables\Columns\TextColumn::make('hourometer')
                    ->label('Horómetro Final'),
                Tables\Columns\IconColumn::make('approved')
                    ->label('Aprobado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('observations')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Observaciones'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approved')
                    ->label('Estado')
                    ->default(0)
                    ->options([
                        1 => 'Completado',
                        0 => 'Pendiente',
                    ]),
                Tables\Filters\SelectFilter::make('operator_id')
                    ->label('Operador')
                    ->visible(fn (Report $record) => in_array(Auth::user()->role, [
                        \App\Enums\RoleType::ADMIN,
                        \App\Enums\RoleType::USUARIOMAQ,
                    ]))
                    ->multiple()
                    ->options(
                        Cache::remember('operators_list', 3600, function () {
                            return User::where('role', \App\Enums\RoleType::OPERARIO)->pluck('name', 'id')->toArray();
                        })
                    ),
                Tables\Filters\Filter::make('fecha')
                    ->columns(2)
                    ->form([
                        DatePicker::make('start_date')->label('Fecha Inicio'),
                        DatePicker::make('end_date')
                            ->default(now())
                            ->label('Fecha Fin'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn ($q) => $q->whereDate('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->whereDate('created_at', '<=', $data['end_date']));
                    }),
                ], layout: FiltersLayout::AboveContent)
                ->filtersFormColumns(3)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->hidden(fn (Report $record) => $record->approved),
                    Action::make('approve_and_consolidate')
                        ->label('Aprobar y Consolidar')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Report $record) {
                            $record->update([
                                'approved' => true,
                                'approved_by' => Auth::id(),
                                'approved_at' => now(),
                            ]);
                            $record->generateConsolidatedReport();
                        })
                        ->visible(fn (Report $record) => !$record->approved && in_array(Auth::user()->role, [
                            \App\Enums\RoleType::ADMIN,
                            \App\Enums\RoleType::USUARIOMAQ,
                        ])),
                ]),
                
            ])
            ->bulkActions([
                    Tables\Actions\BulkAction::make('approve_and_consolidate_bulk')
                        ->label('Aprobar y Consolidar')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $processed = 0;
                            DB::transaction(function () use ($records, &$processed) {
                                foreach ($records as $record) {
                                    if (!$record->approved) {
                                        $record->update([
                                            'approved' => true,
                                            'approved_by' => Auth::id(),
                                            'approved_at' => now(),
                                        ]);
                                        $record->generateConsolidatedReport();
                                        $processed++;
                                    }
                                }
                            });
                            if ($processed > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Reports procesados')
                                    ->body("Reports exitosamente consolidados $processed.")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Reports ya aprobados')
                                    ->body('No se encontraron reports para aprobar.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->authorize(fn () => in_array(Auth::user()->role, [
                            \App\Enums\RoleType::ADMIN,
                            \App\Enums\RoleType::USUARIOMAQ,
                        ])),
                        Tables\Actions\DeleteBulkAction::make(),   
                        ExportBulkAction::make()
                        ->label('Exportar')
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class
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