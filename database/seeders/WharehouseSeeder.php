<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WharehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wharehouses = [
            [
                'name' => 'Bodega 1',
                'status' => 'activo',
                'field_id'=> 1,
                'created_by'=> 1,

            ],
                [
                'name' => 'Bodega 2',
                'status' => 'activo',
                'field_id'=> 1,
                'created_by'=> 1,
            ],
            [
                'name' => 'Bodega 3',
                'status' => 'activo',
                'field_id'=> 1,
                'created_by'=> 1,
            ],
        ];

        foreach ($wharehouses as $wharehouse) {
            \App\Models\Wharehouse::create($wharehouse);
        }
    }
}
