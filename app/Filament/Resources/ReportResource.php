<?php

namespace App\Filament\Resources;

use Closure;
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
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ReportResource\Pages;
use App\Models\User;

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
        $user = User::find(Auth::id());
        if ($user->isOperator()) {
            $form->schema([
                Forms\Components\Hidden::make('operator_id')
                    ->default($user->id),
            ]);
        }
        
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('tractor_id')
                    ->label('Máquina')
                    ->native(false)
                    ->options(function () use ($user) {
                        if ($user->isOperator()) {
                            return Tractor::where('operator_id', $user->id)->pluck('name', 'id')->toArray();
                        }
                        return Tractor::pluck('name', 'id')->toArray();
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
                    ->label('Equipo')
                    ->native(false)
                    ->preload()
                    ->allowHtml()
                    ->options(function (callable $get) use ($user) {
                        if ($user->isOperator()) {
                            return $user->assignedMachineries()->pluck('name', 'id')
                                ->toArray();
                        }
                        return Machinery::pluck('name', 'id')
                            ->toArray();
                    })
                    ->nullable()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('work_id', null)),
                Forms\Components\Select::make('work_id')
                    ->label('Labor')
                    ->native(false)
                    ->options(function (callable $get) {
                        $machineryId = $get('machinery_id');
                        if (!$machineryId) return Work::pluck('name', 'id')->toArray();
                        return Machinery::find($machineryId)->works()->pluck('name', 'id')->toArray();
                    })
                    ->required(),
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
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $initial = (float) $get('initial_hourometer') ?? 0;
                        $set('hours', $state > $initial ? $state - $initial : 0);
                    })
                    ->rules([
                        'numeric',
                        fn ($get) => function (string $attribute, $value, Closure $fail) use ($get) {
                            $initial = (float) $get('initial_hourometer') ?? 0;
                            $maxAllowed = $initial + 12;
                            if ($value > $maxAllowed) {
                                $fail("El horómetro final ({$value}) no puede superar las 12 horas del inicial ({$initial}). Máximo: {$maxAllowed}");
                            }
                            if ($value < $initial) {
                                $fail("El horómetro final ({$value}) no puede ser menor al inicial ({$initial}).");
                            }
                        },
                    ]),
                Forms\Components\TextInput::make('hours')
                    ->label('Horas Trabajadas')
                    ->numeric()
                    ->disabled() 
                    ->dehydrated(true),
                Forms\Components\Textarea::make('observations')
                    ->label('Observaciones')
                    ->nullable(),
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
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operador'),
                Tables\Columns\TextColumn::make('field.name')
                    ->label('Campo'),
                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo'),
                Tables\Columns\TextColumn::make('tractor.name')
                    ->label('Máquina'),
                Tables\Columns\TextColumn::make('machinery.name')
                    ->label('Equipo'),
                Tables\Columns\TextColumn::make('work.name')
                    ->label('Labor'),
                Tables\Columns\TextColumn::make('initial_hourometer')
                    ->label('Horómetro Inicial'),
                Tables\Columns\TextColumn::make('hourometer')
                    ->label('Horómetro Final'),
                Tables\Columns\TextColumn::make('hours')
                    ->label('Horas'),
            
                Tables\Columns\TextColumn::make('observations')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Observaciones'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                Tables\Actions\EditAction::make(),
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
                    ->visible(fn (Report $record) => !$record->approved),
            ])
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