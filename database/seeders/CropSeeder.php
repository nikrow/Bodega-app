<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CropSeeder extends Seeder
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
        $crops = [
            [
                'especie' => 'Palto',
                'created_by'=> 1,
                'updated_by'=> 1,

            ],
            [
                'especie' => 'Mandarino',
                'created_by'=> 1,
                'updated_by'=> 1,
            ],
            [
                'especie' => 'Cerezo',
                'created_by'=> 1,
                'updated_by'=> 1,
            ],
        ];

        foreach ($crops as $crop) {
            \App\Models\Crop::create($crop);
        }
    }
}
