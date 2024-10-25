<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ParcelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = User::first()->id;

        // Verificar que existe al menos un usuario
        if (!$userId) {
            $this->command->error('No hay usuarios en la tabla users. Por favor, ejecuta el seeder de usuarios primero.');
            return;
        }
        $parcels = [
            [
                'name' => 'Mandarino 1',
                'field_id'=> 1,
                'crop_id'=> 1,
                'planting_year'=> 2022,
                'planting_schema'=> '2X2',
                'plants'=> '2000',
                'surface'=> '8',
                'slug'=> 'mandarino-1',
                'created_by'=> 1,

            ],
            [
                'name' => 'Mandarino 2',
                'field_id'=> 1,
                'crop_id'=> 2,
                'planting_year'=> 2022,
                'planting_schema'=> '2X2',
                'plants'=> '2000',
                'surface'=> '8',
                'slug'=> 'mandarino-2',
                'created_by'=> 1,
            ],
            [
                'name' => 'Mandarino 3',
                'field_id'=> 1,
                'crop_id'=> 2,
                'planting_year'=> 2022,
                'planting_schema'=> '2X2',
                'plants'=> '2000',
                'surface'=> '8',
                'slug'=> 'mandarino-3',
                'created_by'=> 1,
            ],
            [
                'name' => 'Palto 1',
                'field_id'=> 1,
                'crop_id'=> 1,
                'planting_year'=> 2022,
                'planting_schema'=> '2X2',
                'plants'=> '2000',
                'surface'=> '8',
                'slug'=> 'palto-1',
                'created_by'=> 1,

            ],
            [
                'name' => 'Palto 2',
                'field_id'=> 1,
                'crop_id'=> 2,
                'planting_year'=> 2022,
                'planting_schema'=> '2X2',
                'plants'=> '2000',
                'surface'=> '8',
                'slug'=> 'palto-2',
                'created_by'=> 1,
            ],
            [
                'name' => 'Palto 3',
                'field_id'=> 1,
                'crop_id'=> 2,
                'planting_year'=> 2022,
                'planting_schema'=> '2X2',
                'plants'=> '2000',
                'surface'=> '8',
                'slug'=> 'palto-3',
                'created_by'=> 1,
            ],
        ];

        foreach ($parcels as $parcel) {
            \App\Models\Parcel::create($parcel);
        }
    }
}
