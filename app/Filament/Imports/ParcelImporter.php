<?php

namespace App\Filament\Imports;

use App\Models\Parcel;
use App\Models\Field;
use App\Models\Crop;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;

class ParcelImporter extends Importer
{
    protected static ?string $model = Parcel::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('Predio')
                ->label('Field (Predio)')
                ->requiredMapping()
                ->relationship(resolveUsing: 'name')
                ->rules(['required']),
            ImportColumn::make('Cuartel')
                ->label('Parcel Name (Cuartel)')
                ->fillRecordUsing(function (Parcel $record, string $state): void {
                    $record->name = $state;
                })
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('Cultivo')
                ->label('Crop (Cultivo)')
                ->requiredMapping()
                ->relationship(resolveUsing: 'name')
                ->rules(['required']),
            ImportColumn::make('Año')
                ->label('Planting Year (Año)')
                ->integer()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('Superficie')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('Plantas Productivas')
                ->label('Plants (Plantas Productivas)')
                ->integer()
                ->rules(['nullable', 'integer']),
        ];
    }

    public function resolveRecord(): ?Parcel
    {
        $field = Field::where('name', $this->data['Predio'])->first();
        if (!$field) {
            return null; 
        }

        return Parcel::firstOrNew([
            'name' => $this->data['Cuartel'],
            'field_id' => $field->id,
        ]);
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $record->is_active = true;
        $record->updated_by = Auth::id();
        if (!$record->wasRecentlyCreated) {
            $record->save();
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importador de cuarteles exitoso ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    protected static function deactivateMissingParcels(Import $import): void
    {
        $importedData = $import->imported_data; 
        $processedNamesByField = collect($importedData)->groupBy('Predio')->map(function ($group) {
            return $group->pluck('Cuartel')->toArray();
        })->toArray();

        $fields = Field::whereIn('name', array_keys($processedNamesByField))->pluck('id', 'name');
        $parcelsToDeactivate = Parcel::where('is_active', true)
            ->where(function ($query) use ($processedNamesByField, $fields) {
                foreach ($processedNamesByField as $fieldName => $names) {
                    $fieldId = $fields[$fieldName] ?? null;
                    if ($fieldId) {
                        $query->orWhere(function ($q) use ($fieldId, $names) {
                            $q->where('field_id', $fieldId)
                              ->whereNotIn('name', $names);
                        });
                    }
                }
            })
            ->get();

        foreach ($parcelsToDeactivate as $parcel) {
            $parcel->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivated_by' => Auth::id(),
                'deactivation_reason' => 'No econtrado en la importación',
            ]);
        }
    }
}