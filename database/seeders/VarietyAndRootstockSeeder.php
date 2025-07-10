<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Variety;
use App\Models\Rootstock;
use App\Models\Crop; // Importa el modelo Crop para obtener los IDs

class VarietyAndRootstockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener los IDs de los cultivos basándose en sus nombres (especie)
        $palta = Crop::where('especie', 'Palto')->first();
        $mandarina = Crop::where('especie', 'Mandarino')->first();
        $cerezo = Crop::where('especie', 'Cerezo')->first();
        

        // Si alguno de los cultivos no existe (por ejemplo, si el CropSeeder no se ha ejecutado),
        // este seeder podría fallar. Se asume que CropSeeder ya se ejecutó.
        if (!$palta || !$mandarina || !$cerezo) {
            $this->command->error("¡Error! Asegúrate de que 'Palta', 'Mandarina' y 'Cerezo' existan en la tabla 'crops' antes de ejecutar este seeder.");
            return;
        }

        $varieties = [
            // Cerezos (crop_id = ID de Cerezo)
            ['name' => 'Lapins', 'crop_id' => $cerezo->id],
            ['name' => 'Santina', 'crop_id' => $cerezo->id],
            ['name' => 'Royal', 'crop_id' => $cerezo->id],
            ['name' => 'Cheery Cupid', 'crop_id' => $cerezo->id],
            ['name' => 'Regina', 'crop_id' => $cerezo->id],
            ['name' => 'Nimba', 'crop_id' => $cerezo->id],
            ['name' => 'Epick 16', 'crop_id' => $cerezo->id],
            ['name' => 'Cherry Nebula', 'crop_id' => $cerezo->id],
            ['name' => 'Pacific Red', 'crop_id' => $cerezo->id],

            // Mandarina (crop_id = ID de Mandarina)
            ['name' => 'W. Murcott', 'crop_id' => $mandarina->id],
            ['name' => 'Shiranui', 'crop_id' => $mandarina->id],

            // Palta (crop_id = ID de Palta)
            ['name' => 'Hass', 'crop_id' => $palta->id],
            ['name' => 'Hass Roja', 'crop_id' => $palta->id],
            ['name' => 'Fuerte', 'crop_id' => $palta->id],
            ['name' => 'Edranol', 'crop_id' => $palta->id],
            ['name' => 'Negra de la Cruz', 'crop_id' => $palta->id],
            ['name' => 'Gem', 'crop_id' => $palta->id],
            ['name' => 'Maluma', 'crop_id' => $palta->id],
            ['name' => 'Velvick', 'crop_id' => $palta->id],
            ['name' => 'Reed', 'crop_id' => $palta->id],
        ];

        foreach ($varieties as $variety) {
            Variety::firstOrCreate(['name' => $variety['name'], 'crop_id' => $variety['crop_id']], $variety);
        }

        $rootstocks = [
            // Cerezos (crop_id = ID de Cerezo)
            ['name' => 'Colt', 'crop_id' => $cerezo->id],
            ['name' => 'Maxma 14', 'crop_id' => $cerezo->id],
            ['name' => 'Maxma 60', 'crop_id' => $cerezo->id],
            ['name' => 'Gisela 6', 'crop_id' => $cerezo->id],
            ['name' => 'Gisela 12', 'crop_id' => $cerezo->id],
            ['name' => 'Ácido', 'crop_id' => $cerezo->id],

            // Mandarina (crop_id = ID de Mandarina)
            ['name' => 'C35', 'crop_id' => $mandarina->id],
            ['name' => 'Carrizo', 'crop_id' => $mandarina->id],
            ['name' => 'Citrumelo', 'crop_id' => $mandarina->id],
            ['name' => 'Macrophylla', 'crop_id' => $mandarina->id],

            // Palta (crop_id = ID de Palta)
            ['name' => 'Merensky 2', 'crop_id' => $palta->id],
            ['name' => 'Duke 7', 'crop_id' => $palta->id],
            ['name' => 'Mexícola', 'crop_id' => $palta->id],
            ['name' => 'Zutano', 'crop_id' => $palta->id],
            ['name' => 'Velvick', 'crop_id' => $palta->id],
        ];

        foreach ($rootstocks as $rootstock) {
            Rootstock::firstOrCreate(['name' => $rootstock['name'], 'crop_id' => $rootstock['crop_id']], $rootstock);
        }
    }
}