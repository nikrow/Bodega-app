<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programa de Fertilización - {{ $program->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos adicionales para impresión */
        body {
            font-family: sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #6b7280;
            padding: 4px 6px;
            text-align: center;
            white-space: nowrap;
        }
        th {
            background-color: #d1d5db;
            font-weight: bold;
        }
        .header-info {
            margin-bottom: 1.5rem;
            font-size: 14px;
        }
        .header-info p {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="header-info">
        <h1>Programa de Fertilización: {{ $program->name }}</h1>
        <p><strong>Campo:</strong> {{ $program->field->name }}</p>
        <p><strong>Cultivo:</strong> {{ $program->crop->name }}</p>
        <p><strong>Período:</strong> {{ $program->start_date->format('d/m/Y') }} - {{ $program->end_date->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Est.</th>
                <th>Nombre Sector</th>
                <th>Año</th>
                <th>SUP</th>
                @foreach($program->fertilizers as $fertilizer)
                    <th>{{ $fertilizer->fertilizerMapping->fertilizer_name }} TOTAL</th>
                    <th>Riegos</th>
                    <th>Litros / riego</th>
                @endforeach
                <!-- Espacios en blanco para rellenar a mano -->
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
            </tr>
        </thead>
        <tbody>
            @foreach($program->parcels as $parcel)
                <tr>
                    <td>{{ $parcel->tank->name ?? 'N/A' }}</td>
                    <td>{{ $parcel->name }}</td>
                    <td>{{ $program->start_date->format('Y') }}</td>
                    <td>{{ number_format($parcel->pivot->area, 2) }}</td>

                    @foreach($program->fertilizers as $fertilizer)
                        @php
                            // Calcular los valores para este cuartel y fertilizante específico
                            $fertilizerAmount = $parcel->pivot->area * $fertilizer->units_per_ha;
                            $applicationQty = $fertilizer->application_quantity > 0 ? $fertilizer->application_quantity : 1;
                            $litrosRiego = ($fertilizerAmount * $fertilizer->dilution_factor) / $applicationQty;
                        @endphp
                        <td>{{ number_format($fertilizerAmount, 2) }}</td>
                        <td>{{ $fertilizer->application_quantity }}</td>
                        <td>{{ number_format($litrosRiego, 2) }}</td>
                    @endforeach

                    <!-- Celdas vacías para rellenar -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>