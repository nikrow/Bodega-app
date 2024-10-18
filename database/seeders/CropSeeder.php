<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CropSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $crops = [
            [
                'especie' => 'Palto',
                'created_by'=> '1',
                'updated_by'=> '1',

            ],
            [
                'especie' => 'Mandarino',
                'created_by'=> '1',
                    'updated_by'=> '1',
            ],
            [
                'especie' => 'Cerezo',
                'created_by'=> '1',
                'updated_by'=> '1',
            ],
        ];

        foreach ($crops as $crop) {
            \App\Models\Crop::create($crop);
        }
    }
}
