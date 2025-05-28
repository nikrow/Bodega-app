<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Irrigation;
use App\Models\Fertilization;
use App\Models\FertilizerMapping;
use Illuminate\Database\Eloquent\Collection;

class FertilizationTable extends Component
{
    public $irrigationId;
    public $fertilizations = [];
    public $quantitySolutions = [];

    protected $listeners = ['refreshTable' => '$refresh'];

    public function mount($irrigationId)
    {
        $this->irrigationId = $irrigationId;
        $this->loadFertilizations();
    }

    public function loadFertilizations()
    {
        $irrigation = Irrigation::findOrFail($this->irrigationId);
        $this->fertilizations = $irrigation->fertilization()
            ->with(['product', 'fertilizerMapping'])
            ->get()
            ->toArray();
        
        foreach ($this->fertilizations as $fertilization) {
            $this->quantitySolutions[$fertilization['id']] = $fertilization['quantity_solution'];
        }
    }

    public function updateQuantitySolution($fertilizationId, $value)
    {
        $fertilization = Fertilization::findOrFail($fertilizationId);
        $mapping = FertilizerMapping::where('id', $fertilization->fertilizer_mapping_id)->first();
        $dilution_factor = $mapping ? $mapping->dilution_factor : 0;
        $product_price = $mapping && $mapping->product ? $mapping->product->price : 0;

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

        \Filament\Notifications\Notification::make()
            ->title('Cantidad actualizada')
            ->success()
            ->send();
    }

    public function deleteFertilization($fertilizationId)
    {
        $fertilization = Fertilization::findOrFail($fertilizationId);
        $fertilization->delete();

        $this->loadFertilizations();

        \Filament\Notifications\Notification::make()
            ->title('Fertilización eliminada')
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.fertilization-table');
    }
}