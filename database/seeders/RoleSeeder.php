<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
            'name'=> 'admin',
            ],
            [
                'name'=> 'agronomo',
            ],
            [
                'name'=> 'usuario',
            ],
            [
                'name'=> 'bodeguero',
            ],
            [
                'name'=> 'asistente',
            ],
            [
                'name'=> 'estanquero',
            ]
        ];

        foreach ($roles as $role) {
            \App\Models\Role::create($role);
        }
  }
}
