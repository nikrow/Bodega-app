<div class="overflow-x-auto">
    <table class="w-full table-auto">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-4 py-2 text-left">ID</th>
                <th class="px-4 py-2 text-left">Fecha</th>
                <th class="px-4 py-2 text-left">Cuartel</th>
                <th class="px-4 py-2 text-left">Riego</th>
                <th class="px-4 py-2 text-left">Fertilizante</th>
                <th class="px-4 py-2 text-left">Superficie</th>
                <th class="px-4 py-2 text-left">Cantidad Solución</th>
                <th class="px-4 py-2 text-left">Factor de Dilución</th>
                <th class="px-4 py-2 text-left">Cantidad Producto</th>
                <th class="px-4 py-2 text-left">Precio Producto</th>
                <th class="px-4 py-2 text-left">Costo Total</th>
                <th class="px-4 py-2 text-left">Método Aplicación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fertilizations as $fertilization)
                <tr wire:key="{{ $fertilization['id'] }}">
                    <td class="border px-4 py-2">{{ $fertilization['id'] }}</td>
                    <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($fertilization['date'])->format('d/m/Y') }}</td>
                    <td class="border px-4 py-2">
                        <select
                            wire:model.live="fertilization.parcel_id"
                            wire:change="updateParcel({{ $fertilization['id'] }}, $event.target.value)"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        >
                            @foreach($parcels as $id => $name)
                                <option value="{{ $id }}" {{ $fertilization['parcel_id'] == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="border px-4 py-2">
                        <select
                            wire:model.live="fertilization.irrigation_id"
                            wire:change="updateIrrigation({{ $fertilization['id'] }}, $event.target.value)"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        >
                            @foreach($irrigations[$fertilization['parcel_id']] ?? [] as $id => $date)
                                <option value="{{ $id }}" {{ $fertilization['irrigation_id'] == $id ? 'selected' : '' }}>{{ $date }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="border px-4 py-2">
                        <select
                            wire:model.live="fertilization.fertilizer_mapping_id"
                            wire:change="updateFertilizerMapping({{ $fertilization['id'] }}, $event.target.value)"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        >
                            @foreach($fertilizerMappings as $id => $name)
                                <option value="{{ $id }}" {{ $fertilization['fertilizer_mapping_id'] == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="border px-4 py-2">{{ $fertilization['surface'] }}</td>
                    <td class="border px-4 py-2">
                        <input
                            type="number"
                            wire:model.live="quantitySolutions.{{ $fertilization['id'] }}"
                            wire:change="updateQuantitySolution({{ $fertilization['id'] }}, $event.target.value)"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            min="0"
                            step="0.01"
                        />
                    </td>
                    <td class="border px-4 py-2">{{ $fertilization['dilution_factor'] }}</td>
                    <td class="border px-4 py-2">{{ $fertilization['quantity_product'] }}</td>
                    <td class="border px-4 py-2">{{ $fertilization['product_price'] }}</td>
                    <td class="border px-4 py-2">{{ $fertilization['total_cost'] }}</td>
                    <td class="border px-4 py-2">{{ $fertilization['application_method'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="border px-4 py-2 text-center">No hay fertilizaciones registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>