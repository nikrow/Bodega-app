<?php

namespace Database\Seeders;

use App\Models\PlantingScheme;
use Illuminate\Database\Seeder;

class PlantingSchemeSeeder extends Seeder
{
    public function run(): void
    {
        $schemes = [
            '2,5 x 1,25',
            '2,5 x 2,5',
            '2,5 x 3,10',
            '2,8 x 1,55',
            '3 x 2,5',
            '5 x 1,25',
            '5 x 2',
            '5 x 2,5',
            '5 x 3',
            '5 x 3,10',
            '5 x 4',
            '6 x 2',
            '6 x 2,5',
            '6 x 6',
            '7 x 1,5',
        ];

        foreach ($schemes as $scheme) {
            PlantingScheme::firstOrCreate(['scheme' => $scheme]);
        }
    }
}