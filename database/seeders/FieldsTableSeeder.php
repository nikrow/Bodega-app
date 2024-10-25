<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Field;
use App\Models\User; // Asegúrate de importar el modelo User

class FieldsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Obtener el ID de un usuario existente para asignar a 'created_by'
        // Por ejemplo, el primer usuario
        $userId = User::first()->id;

        // Verificar que existe al menos un usuario
        if (!$userId) {
            $this->command->error('No hay usuarios en la tabla users. Por favor, ejecuta el seeder de usuarios primero.');
            return;
        }

        // Crear campos específicos
        $fields = [
            [
                'name' => 'Las Palmas',
                'slug' => 'las-palmas',
                'created_by' => 1,
            ],
            [
                'name' => 'DASA',
                'slug' => 'dasa',
                'created_by' => 1,
            ],
            [
                'name' => 'Llayquen',
                'slug' => 'llayquen',
                'created_by' => 1,
            ],
            [
                'name' => 'Santa Isabel',
                'slug' => 'santa-isabel',
                'created_by' => 1,
            ],
        ];

        foreach ($fields as $field) {
            Field::create($field);
        }

    }
}
