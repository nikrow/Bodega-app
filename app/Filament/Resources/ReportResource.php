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
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Resources\ReportResource\Pages;
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
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $tractor = Tractor::find($state);
                    $set('initial_hourometer', $tractor?->hourometer ?? 0);
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
                ->nullable()
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
                ->disabled()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, $get) {
                    $final = (float) $get('hourometer') ?? 0;
                    $set('hours', $final > $state ? $final - $state : 0);
                }),
                Forms\Components\TextInput::make('hourometer')
                ->label('Horómetro Final')
                ->numeric()
                ->inputMode('decimal') // Mantiene el teclado numérico con decimales
                ->step(0.01)
                ->required()
                ->afterStateUpdated(function ($state, callable $set, $get) {
                    // Normalizar el valor ingresado reemplazando coma por punto
                    $state = str_replace(',', '.', $state);
                    $initial = (float) $get('initial_hourometer') ?? 0;
                    $set('hours', $state > $initial ? $state - $initial : 0);
                })
                ->formatStateUsing(function ($state) {
                    // Normalizar el valor para visualización y procesamiento
                    return str_replace(',', '.', $state);
                })
                ->rules([
                    'numeric',
                    fn ($get) => function (string $attribute, $value, Closure $fail) use ($get) {
                        // Normalizar el valor antes de validar
                        $value = str_replace(',', '.', $value);
                        $initial = (float) $get('initial_hourometer') ?? 0;
                        $isEditing = $get('id') !== null; // Si id no es null, estamos editando
                        $tractorId = $get('tractor_id');
                        if ($tractorId && !$isEditing) { // Solo aplica en creación
                            $tractor = Tractor::find($tractorId);
                            $currentHourometer = $tractor->hourometer ?? 0;
                            if ($initial != $currentHourometer) {
                                $fail("El horómetro inicial no coincide, favor recargue la página.");
                            }
                            if ($value < $currentHourometer) {
                                $fail("El horómetro final no puede ser menor que el horómetro actual del tractor ({$currentHourometer}).");
                            }
                        }
                        if ($value < $initial) {
                            $fail("El horómetro final ({$value}) no puede ser menor al inicial ({$initial}).");
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
    // Condición para el campo operator_id
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
                    ->label('Máquina'),
                Tables\Columns\TextColumn::make('machinery.name')
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
                        Tables\Actions\DeleteBulkAction::make()   
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