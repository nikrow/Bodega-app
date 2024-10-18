<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'product_name' => 'insecticida',
                'active_ingredients' => 'insecticida 10%',
                'SAP_code' => '101515',
                'SAP_family' => 'fitosanitarios',
                'family' => 'insecticida',
                'price' => 10000,
                'waiting_time' => 10,
                'reentry' => 20,
                'created_by' => 1,
                'updated_by' => 1,
                'unit_measure' => 'kilogramo',
                'field_id' => 1,
                'slug' => 'insecticida',
                'dosis_min' => 1,
                'dosis_max' => 10,
            ],
            [
                'product_name' => 'herbicida',
                'active_ingredients' => 'herbicida 3%',
                'SAP_code' => '199995',
                'SAP_family' => 'fitosanitarios',
                'family' => 'herbicida',
                'price' => 10,
                'waiting_time' => 10,
                'reentry' => 20,
                'created_by' => 1,
                'updated_by' => 1,
                'unit_measure' => 'kilogramo',
                'field_id' => 1,
                'slug' => 'herbicida',
                'dosis_min' => 1,
                'dosis_max' => 10,
            ],
            [
                'product_name' => 'fertilizante',
                'active_ingredients' => 'fertilizante 10%',
                'SAP_code' => '103540',
                'SAP_family' => 'fertilizantes-enmiendas',
                'family' => 'fertilizante',
                'price' => 50,
                'waiting_time' => 10,
                'reentry' => 20,
                'created_by' => 1,
                'updated_by' => 1,
                'unit_measure' => 'kilogramo',
                'field_id' => 1,
                'slug' => 'fertilizante',
                'dosis_min' => 1,
                'dosis_max' => 10,
            ],
            [
                'product_name' => 'acaricida',
                'active_ingredients' => 'acaricida 100%',
                'SAP_code' => '101520',
                'SAP_family' => 'fitosanitarios',
                'family' => 'acaricida',
                'price' => 100,
                'waiting_time' => 10,
                'reentry' => 20,
                'created_by' => 1,
                'updated_by' => 1,
                'unit_measure' => 'kilogramo',
                'field_id' => 1,
                'slug' => 'acaricida',
                'dosis_min' => 1,
                'dosis_max' => 10,
            ],
            ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
