<?php

namespace App\Imports;

use App\Models\Irrigation;
use App\Models\Parcel;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class IrrigationImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        
        $parcel = Parcel::where('name', $row['parcel_name'])->first();

        if (!$parcel) {
            return null; 
        }

        $field = $parcel->fields()->first();

        if (!$field) {
            
            return null;
        }

        return new Irrigation([
            'parcel_id' => $parcel->id,
            'field_id' => $field->id,
            'date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date']),
            'surface' => $parcel->surface ?? 0.00, 
            'duration' => $row['duration'],
            'quantity_m3' => $row['quantity_m3'],
            'type' => 'default', 
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
}