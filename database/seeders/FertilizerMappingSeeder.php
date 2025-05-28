<?php
namespace Database\Seeders;

use App\Models\FertilizerMapping;
use Illuminate\Database\Seeder;

class FertilizerMappingSeeder extends Seeder
{
    public function run()
    {
        $mappings = [
            [
                'excel_column_name' => 'acido_fosforico_10_l',
                'fertilizer_name' => 'Acido Fosforico-10%',
                'product_id' => 65,
                'dilution_factor' => 0.1,
            ],
            [
                'excel_column_name' => 'ferpac_n22_100_l',
                'fertilizer_name' => 'Ferpac N22-100%',
                'product_id' => 63,
                'dilution_factor' => 1,
            ],
            [
                'excel_column_name' => 'ferpac_n22_25_l',
                'fertilizer_name' => 'Ferpac N22-25%',
                'product_id' => 63,
                'dilution_factor' => 0.25,
            ],
            [
                'excel_column_name' => 'ferpac_zn_27_10_l',
                'fertilizer_name' => 'Ferpac Zn 27-10%',
                'product_id' => 64,
                'dilution_factor' => 0.1,
            ],
            [
                'excel_column_name' => 'hipoclorito_de_sodio_5_l',
                'fertilizer_name' => 'Hipoclorito de sodio-5%',
                'product_id' => 105,
                'dilution_factor' => 0.05,
            ],
            [
                'excel_column_name' => 'humic_soil_20_l',
                'fertilizer_name' => 'Humic Soil-20%',
                'product_id' => 83,
                'dilution_factor' => 0.2,
            ],
            [
                'excel_column_name' => 'humic_soil_50_l',
                'fertilizer_name' => 'Humic Soil-50%',
                'product_id' => 83,
                'dilution_factor' => 0.5,
            ],
            [
                'excel_column_name' => 'nitrato_de_calcio_100_l',
                'fertilizer_name' => 'Nitrato de Calcio-100%',
                'product_id' => 85,
                'dilution_factor' => 1,
            ],
            [
                'excel_column_name' => 'nutrafol_amino_25_l',
                'fertilizer_name' => 'Nutrafol Amino-25%',
                'product_id' => 87,
                'dilution_factor' => 0.25,
            ],
            [
                'excel_column_name' => 'nutrafol_boro_21_5_l',
                'fertilizer_name' => 'Nutrafol Boro 21-5%',
                'product_id' => 110,
                'dilution_factor' => 0.05,
            ],
            [
                'excel_column_name' => 'nutrafol_molifos_100_l',
                'fertilizer_name' => 'Nutrafol Molifos-100%',
                'product_id' => 90,
                'dilution_factor' => 1,
            ],
            [
                'excel_column_name' => 'sulfato_de_magnesio_20_l',
                'fertilizer_name' => 'Sulfato de Magnesio-20%',
                'product_id' => 95,
                'dilution_factor' => 0.2,
            ],
            [
                'excel_column_name' => 'sulfato_de_potasio_10_l',
                'fertilizer_name' => 'Sulfato de Potasio-10%',
                'product_id' => 104,
                'dilution_factor' => 0.1,
            ],
            [
                'excel_column_name' => 'ultrasol_crecimiento_100_l',
                'fertilizer_name' => 'Ultrasol crecimiento-100%',
                'product_id' => 106,
                'dilution_factor' => 1,
            ],
            [
                'excel_column_name' => 'ultrasol_crecimiento_50_l',
                'fertilizer_name' => 'Ultrasol crecimiento-50%',
                'product_id' => 106,
                'dilution_factor' => 0.5,
            ],
            [
                'excel_column_name' => 'ultrasol_k_acid_20_l',
                'fertilizer_name' => 'Ultrasol K Acid-20%',
                'product_id' => 97,
                'dilution_factor' => 0.2,
            ],
            [
                'excel_column_name' => 'ultrasol_multiproposito_18_18_18_100_l',
                'fertilizer_name' => 'Ultrasol Multiproposito 18-18-18-100%',
                'product_id' => 98,
                'dilution_factor' => 1,
            ],
            [
                'excel_column_name' => 'vitra_crece_100_l',
                'fertilizer_name' => 'Vitra Crece-100%',
                'product_id' => 135,
                'dilution_factor' => 1,
            ],
            [
                'excel_column_name' => 'fosfato_monoamonico_20_l',
                'fertilizer_name' => 'Fosfato Monoamonico-20%',
                'product_id' => 81,
                'dilution_factor' => 0.2,
            ],
        ];

        foreach ($mappings as $mapping) {
            FertilizerMapping::create([
                'excel_column_name' => $mapping['excel_column_name'],
                'product_id' => $mapping['product_id'],
                'dilution_factor' => $mapping['dilution_factor'],
                'fertilizer_name' => $mapping['fertilizer_name'],
            ]);
        }
    }
}