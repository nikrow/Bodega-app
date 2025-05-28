<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Irrigation;
use App\Models\Fertilization;
use App\Models\FertilizerMapping;
use App\Models\Parcel;

class FertilizationResourceManager extends Component
{
    public $fertilizations = [];
    public $parcels = [];
    public $fertilizerMappings = [];
    public $irrigations = [];
    public $quantitySolutions = [];

    public function mount()
    {
        $this->loadFertilizations();
        $this->parcels = Parcel::all()->pluck('name', 'id')->toArray();
        $this->fertilizerMappings = FertilizerMapping::with('product')->get()->mapWithKeys(function ($mapping) {
            return [$mapping->id => $mapping->fertilizer_name . ' (' . $mapping->product->product_name . ')'];
        })->toArray();
        $this->irrigations = Irrigation::all()->groupBy('parcel_id')->map(function ($group) {
            return $group->pluck('date', 'id')->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('d/m/Y');
            })->toArray();
        })->toArray();
    }

    public function loadFertilizations()
    {
        $this->fertilizations = Fertilization::with(['parcel', 'irrigation', 'fertilizerMapping.product'])->get()->toArray();
        
        foreach ($this->fertilizations as $fertilization) {
            $this->quantitySolutions[$fertilization['id']] = $fertilization['quantity_solution'];
        }
    }

    public function updateParcel($fertilizationId, $parcelId)
    {
        $fertilization = Fertilization::find($fertilizationId);
        if ($fertilization) {
            $parcel = Parcel::find($parcelId);
            $fertilization->update([
                'parcel_id' => $parcelId,
                'surface' => $parcel ? $parcel->surface : 0,
            ]);
            $this->loadFertilizations();
            \Filament\Notifications\Notification::make()->title('Cuartel actualizado')->success()->send();
        }
    }

    public function updateIrrigation($fertilizationId, $irrigationId)
    {
        $fertilization = Fertilization::find($fertilizationId);
        if ($fertilization) {
            $fertilization->update(['irrigation_id' => $irrigationId]);
            $this->loadFertilizations();
            \Filament\Notifications\Notification::make()->title('Riego actualizado')->success()->send();
        }
    }

    public function updateFertilizerMapping($fertilizationId, $mappingId)
    {
        $fertilization = Fertilization::find($fertilizationId);
        if ($fertilization) {
            $mapping = FertilizerMapping::find($mappingId);
            if ($mapping) {
                $dilution_factor = $mapping->dilution_factor;
                $quantity_solution = $fertilization->quantity_solution;
                $product_price = $mapping->product->price ?? 0;
                $quantity_product = is_numeric($quantity_solution) && is_numeric($dilution_factor)
                    ? round($quantity_solution * $dilution_factor, 2)
                    : 0;
                $total_cost = $quantity_product * $product_price;

                $fertilization->update([
                    'fertilizer_mapping_id' => $mappingId,
                    'product_id' => $mapping->product_id,
                    'dilution_factor' => $dilution_factor,
                    'quantity_product' => $quantity_product,
                    'product_price' => $product_price,
                    'total_cost' => $total_cost,
                ]);

                $this->loadFertilizations();
                \Filament\Notifications\Notification::make()->title('Fertilizante actualizado')->success()->send();
            }
        }
    }

    public function updateQuantitySolution($fertilizationId, $value)
    {
        $fertilization = Fertilization::find($fertilizationId);
        if ($fertilization) {
            $dilution_factor = $fertilization->dilution_factor;
            $product_price = $fertilization->product_price ?? 0;
            $quantity_solution = is_numeric($value) ? (float)$value : 0;
            $quantity_product = is_numeric($quantity_solution) && is_numeric($dilution_factor)
                ? round($quantity_solution * $dilution_factor, 2)
                : 0;
            $total_cost = $quantity_product * $product_price;

            $fertilization->update([
                'quantity_solution' => $quantity_solution,
                'quantity_product' => $quantity_product,
                'total_cost' => $total_cost,
            ]);

            $this->loadFertilizations();
            \Filament\Notifications\Notification::make()->title('Cantidad actualizada')->success()->send();
        }
    }

    public function render()
    {
        return view('livewire.fertilization-resource-manager');
    }
}