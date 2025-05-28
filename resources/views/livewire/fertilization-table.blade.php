<div class="overflow-x-auto">
    <table class="w-full table-auto">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-4 py-2 text-left">ID</th>
                <th class="px-4 py-2 text-left">Fecha</th>
                <th class="px-4 py-2 text-left">Fertilizante</th>
                <th class="px-4 py-2 text-left">Superficie</th>
                <th class="px-4 py-2 text-left">Cantidad Solución</th>
                <th class="px-4 py-2 text-left">Factor de Dilución</th>
                <th class="px-4 py-2 text-left">Cantidad Producto</th>
                <th class="px-4 py-2 text-left">Precio Producto</th>
                <th class="px-4 py-2 text-left">Costo Total</th>
                <th class="px-4 py-2 text-left">Método Aplicación</th>
                <th class="px-4 py-2 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fertilizations as $fertilization)
                <tr>
                    <td class="border px-4 py-2">{{ $fertilization['id'] }}</td>
                    <td class="border px-4 py-2">{{ \Carbon\Risk\Carbon\Carbon::parse($fertilization['date'])->format('d/m/Y') }}</td>
                    <td class="border px-4 py-2">{{ $fertilization['fertilizer_mapping']['fertilizer_name'] ?? '-' }} ({{ $fertilization['product']['product_name'] ?? '-' }})</td>
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
                    <td class="border px-4 py-2">
                        <button
                            wire:click="deleteFertilization({{ $fertilization['id'] }})"
                            class="text-red-600 hover:text-red-800"
                        >
                            Eliminar
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="border px-4 py-2 text-center">No hay fertilizaciones registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>