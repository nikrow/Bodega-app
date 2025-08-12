<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Parcel;
use App\Models\Program;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProgramParcel;
use Filament\Facades\Filament;
use App\Models\FertilizerMapping;
use App\Models\ProgramFertilizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;
use Filament\Resources\RelationManagers\RelationManager;

class ParcelsRelationManager extends RelationManager
{
    protected static string $relationship = 'parcels';
    protected static ?string $title = 'Cuarteles';
    protected static ?string $modelLabel = 'Cuartel';
    protected static ?string $pluralModelLabel = 'Cuarteles';

    /**
     * Define el formulario para crear y editar registros en la relación.
     * Este formulario se usa en el modal de edición.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('program_id')
                    ->default($this->getOwnerRecord()->id),

                Forms\Components\TextInput::make('area')
                    ->numeric()
                    ->label('Superficie (ha)')
                    ->required()
                    ->reactive()
                    // Recalcula la cantidad de fertilizante cuando el área cambia.
                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                        if ($get('fertilizer_mapping_id')) {
                            $programFertilizer = ProgramFertilizer::where('program_id', $get('program_id'))
                                ->where('fertilizer_mapping_id', $get('fertilizer_mapping_id'))
                                ->first();
                            if ($programFertilizer) {
                                $set('fertilizer_amount', $programFertilizer->units_per_ha * $state);
                            }
                        }
                    }),
                
                Forms\Components\Select::make('fertilizer_mapping_id')
                    ->label('Fertilizante')
                    // Carga solo los fertilizantes asociados a este programa.
                    ->options(function (callable $get) {
                        $programId = $get('program_id');
                        return FertilizerMapping::whereIn(
                            'id',
                            ProgramFertilizer::where('program_id', $programId)->pluck('fertilizer_mapping_id')
                        )->pluck('fertilizer_name', 'id');
                    })
                    ->reactive()
                    // Recalcula la cantidad de fertilizante cuando el fertilizante cambia.
                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                        $programId = $get('program_id');
                        $programFertilizer = ProgramFertilizer::where('program_id', $programId)
                            ->where('fertilizer_mapping_id', $state)
                            ->first();
                        
                        if ($programFertilizer && $get('area')) {
                            $set('fertilizer_amount', $programFertilizer->units_per_ha * $get('area'));
                        } else {
                            $set('fertilizer_amount', null);
                        }
                    })
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('fertilizer_amount')
                    ->numeric()
                    ->label('Cantidad de Fertilizante (bruto)')
                    ->required(),
            ]);
    }

    /**
     * Define la tabla que muestra los registros de la relación.
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('parcel.name')
            ->columns([
                Tables\Columns\TextColumn::make('pivot.parcel.name')
                    ->label('Cuartel')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->label('Superficie (ha)')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.fertilizerMapping.fertilizer_name')
                    ->label('Fertilizante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fertilizer_amount')
                    ->label('Cantidad de Fertilizante (bruto)')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                $this->getAttachAction()
                ->visible(fn($record) => $this->getOwnerRecord()->fertilizers()->count() > 0),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    $this->getUpdateAreasBulkAction(),
                ]),
            ]);
    }

    /**
     * Construye la consulta base para obtener los cuarteles elegibles.
     * Esto evita la duplicación de código (DRY).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getParcelQuery(): Builder
    {
        return Parcel::query()
            ->where('field_id', Filament::getTenant()->id)
            ->where('crop_id', $this->getOwnerRecord()->crop_id);
    }
    
    /**
 * Define la acción para adjuntar nuevos cuarteles al programa.
 *
 * @return Tables\Actions\AttachAction
 */
protected function getAttachAction(): Tables\Actions\AttachAction
{
    return Tables\Actions\AttachAction::make()
        ->label('Asociar Cuarteles')
        ->icon('heroicon-o-plus')
        ->color('primary')
        ->recordSelect(
            fn (Select $select) => $select
                ->options($this->getParcelQuery()->pluck('name', 'id'))
                ->getSearchResultsUsing(fn (string $search) =>
                    $this->getParcelQuery()
                        ->where('name', 'like', "%{$search}%")
                        ->pluck('name', 'id')
                )
        )
        ->form(fn (Tables\Actions\AttachAction $action): array => [
            $action->getRecordSelect(),
            Forms\Components\TextInput::make('total_surface')
                ->label('Suma de Superficies (ha)')
                ->numeric()
                ->reactive()
                ->default(0)
                ->disabled()
                ->afterStateHydrated(function ($set, $get) {
                    $selectedParcels = $get('recordId') ?? [];
                    if (is_array($selectedParcels) && count($selectedParcels) > 0) {
                        $set('total_surface', Parcel::whereIn('id', $selectedParcels)->sum('surface'));
                    }
                }),
        ])
        ->preloadRecordSelect()
        ->multiple()
        ->action(function (array $data): void {
            $program = $this->getOwnerRecord();
            $originalSelectedParcelIds = $data['recordId'] ?? [];

            if (empty($originalSelectedParcelIds)) {
                return;
            }

            $validParcelIds = [];
            $conflictMessages = [];
            $newStartDate = $program->start_date;
            $newEndDate = $program->end_date;

            // 1. Separar los cuarteles válidos de los conflictivos
            foreach ($originalSelectedParcelIds as $parcelId) {
                $parcel = Parcel::find($parcelId);
                if (!$parcel) continue;

                $conflictingProgram = Program::where('id', '!=', $program->id)
                    ->whereHas('parcels', fn ($q) => $q->where('parcel_id', $parcelId))
                    ->where('start_date', '<=', $newEndDate)
                    ->where('end_date', '>=', $newStartDate)
                    ->first();

                if ($conflictingProgram) {
                    // Si hay conflicto, lo guardamos para notificar al final
                    $conflictMessages[] = "Cuartel '{$parcel->name}': Ya está en el programa '{$conflictingProgram->name}'.";
                } else {
                    // Si no hay conflicto, lo agregamos a la lista de válidos
                    $validParcelIds[] = $parcelId;
                }
            }

            // 2. Procesar e insertar solo los cuarteles válidos
            if (!empty($validParcelIds)) {
                $parcelsToInsert = Parcel::whereIn('id', $validParcelIds)->get()->keyBy('id');
                $programFertilizers = ProgramFertilizer::where('program_id', $program->id)->get();
                $dataToInsert = [];
                $now = Date::now();

                foreach ($validParcelIds as $parcelId) { // Iteramos sobre los ID válidos
                    $parcel = $parcelsToInsert->get($parcelId);
                    if (!$parcel) continue;

                    $area = $parcel->surface;
                    
                    if ($programFertilizers->isEmpty()) {
                        $dataToInsert[] = [
                            'program_id' => $program->id,
                            'parcel_id' => $parcelId,
                            'field_id' => Filament::getTenant()->id,
                            'area' => $area,
                            'fertilizer_mapping_id' => null,
                            'fertilizer_amount' => null,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    } else {
                        foreach ($programFertilizers as $programFertilizer) {
                            $dataToInsert[] = [
                                'program_id' => $program->id,
                                'parcel_id' => $parcelId,
                                'field_id' => Filament::getTenant()->id,
                                'area' => $area,
                                'fertilizer_mapping_id' => $programFertilizer->fertilizer_mapping_id,
                                'fertilizer_amount' => $programFertilizer->units_per_ha * $area,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }

                if (!empty($dataToInsert)) {
                    ProgramParcel::insert($dataToInsert);
                }

                Notification::make()
                    ->success()
                    ->title('Asociación Exitosa')
                    ->body('Se asociaron ' . count($validParcelIds) . ' cuartel(es) correctamente.')
                    ->sendToDatabase(auth()->user());
            }

            // 3. Notificar al usuario sobre los conflictos encontrados (si los hay)
            if (!empty($conflictMessages)) {
                Notification::make()
                    ->warning() // Usamos 'warning' porque parte de la operación fue exitosa
                    ->title('Algunos Cuarteles No se Pudieron Asociar')
                    ->body(implode("\n", $conflictMessages))
                    ->persistent() // La dejamos visible hasta que el usuario la cierre
                    ->sendToDatabase(auth()->user());
            }

            // Si no hubo cuarteles válidos ni conflictos (ej. IDs no encontrados), no hacemos nada.
            if (empty($validParcelIds) && empty($conflictMessages)) {
                 Notification::make()
                    ->warning()
                    ->title('No se asoció ningún cuartel')
                    ->body('No se encontraron cuarteles válidos para asociar.')
                    ->sendToDatabase(auth()->user());
            }

        });
}

    /**
     * Define la acción masiva para sincronizar las superficies de los cuarteles seleccionados.
     *
     * @return Tables\Actions\BulkAction
     */
    protected function getUpdateAreasBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('update_areas')
            ->label('Actualizar Superficies')
            ->requiresConfirmation()
            ->form(function (Collection $records) {
                $differences = [];
                // Recorre los registros seleccionados para detectar diferencias.
                foreach ($records as $record) {
                    $parcel = $record->parcel;
                    if ($parcel && $record->area != $parcel->surface) {
                        $fertilizerName = $record->fertilizerMapping?->fertilizer_name ?? 'N/A';
                        // La clave es el ID del registro ProgramParcel, el valor es el texto a mostrar.
                        $differences[$record->id] = "Cuartel '{$parcel->name}': {$record->area} ha → {$parcel->surface} ha (Fert: {$fertilizerName})";
                    }
                }

                if (empty($differences)) {
                    return []; // No mostrar formulario si no hay diferencias.
                }

                return [
                    Section::make('Diferencias Detectadas')
                        ->description('Selecciona las superficies que deseas actualizar. La cantidad de fertilizante se recalculará automáticamente.')
                        ->schema([
                            CheckboxList::make('selected_differences')
                                ->label('Diferencias a Actualizar')
                                ->options($differences)
                                ->default(array_keys($differences)) // Seleccionar todas por defecto
                                ->columns(1)
                                ->required(),
                        ]),
                ];
            })
            ->action(function (array $data, Collection $records) {
                $selectedIds = $data['selected_differences'] ?? null;

                // Si no hay diferencias detectadas, el formulario no se muestra y $selectedIds será null.
                if ($selectedIds === null) {
                    if ($records->every(fn($record) => !$record->parcel || $record->area == $record->parcel->surface)) {
                        Notification::make()->success()->title('No hay diferencias de superficie para actualizar.')->send();
                        return;
                    }
                    // Si hay diferencias pero el usuario no seleccionó ninguna (caso improbable si es `required`).
                    $selectedIds = [];
                }

                if (empty($selectedIds)) {
                     Notification::make()->warning()->title('No se seleccionó ninguna superficie para actualizar.')->send();
                     return;
                }
                
                $updatedCount = 0;
                // Obtenemos todos los fertilizantes del programa una sola vez.
                $programId = $records->first()->program_id;
                $programFertilizers = ProgramFertilizer::where('program_id', $programId)
                    ->get()
                    ->keyBy('fertilizer_mapping_id');

                foreach ($records as $record) {
                    // Solo actualiza si el registro fue seleccionado en el CheckboxList.
                    if (in_array($record->id, $selectedIds)) {
                        $newArea = $record->parcel->surface;
                        $newFertilizerAmount = $record->fertilizer_amount;

                        // Recalcula la cantidad de fertilizante si existe.
                        $programFertilizer = $programFertilizers->get($record->fertilizer_mapping_id);
                        if ($programFertilizer) {
                            $newFertilizerAmount = $programFertilizer->units_per_ha * $newArea;
                        }

                        $record->update([
                            'area' => $newArea,
                            'fertilizer_amount' => $newFertilizerAmount,
                            'updated_by' => Auth::id(),
                        ]);
                        $updatedCount++;
                    }
                }

                if ($updatedCount > 0) {
                    Notification::make()
                        ->success()
                        ->title("Se actualizaron {$updatedCount} superficies correctamente.")
                        ->sendToDatabase(auth()->user());
                }
            });
    }
}