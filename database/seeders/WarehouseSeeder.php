<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
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
        $warehouses = [
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

        foreach ($warehouses as $warehouse) {
            \App\Models\Warehouse::create($warehouse);
        }
    }
}
