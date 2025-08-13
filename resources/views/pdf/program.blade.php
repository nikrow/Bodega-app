{{-- resources/views/programa-fertilizacion.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programa de Fertilización - {{ $program->name }}</title>
    <style>
        @page { margin: 10mm; }

        body {
            font-family: 'Manrope', Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        /* Encabezado ultra compacto */
        .header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 4px 8px 2px 8px;
        }
        .header img { max-width: 42px; height: auto; }
        .header-info h3 { margin: 0; font-size: 10px; }
        .header-info h4 { margin: 0; font-size: 9px; color: #555; }

        .meta {
            margin: 0 8px 6px 8px;
            font-size: 9px;
            line-height: 1.2;
            color: #333;
        }

        .main-title {
            text-align: center;
            font-size: 12px;
            margin: 2px 8px 6px 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        /* Tabla optimizada */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* evita que cambie el ancho relativo de columnas */
        }
        thead { display: table-header-group; } /* repetir encabezado en cada página */
        th, td {
            border: 1px solid #ddd;
            padding: 3px;
            text-align: center;
            font-size: clamp(7px, 0.8vw, 9px);
            line-height: 1.15;
            vertical-align: middle;
            word-break: break-word;
            white-space: normal;
        }
        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Encabezado -->
    <div class="header">
        <img src="{{ public_path('img/logogjs.webp') }}" alt="Logo">
        <div class="header-info">
            <h3>Jorge Schmidt y Cia Ltda</h3>
            <h4>Llay Llay, Chile</h4>
        </div>
    </div>

    <!-- Título -->
    <h1 class="main-title">Programa de Fertilización: {{ $program->name }}</h1>

    <!-- Meta compacta -->
    <div class="meta">
        <strong>Campo:</strong> {{ $program->field->name ?? 'N/A' }} &nbsp;|&nbsp;
        <strong>Cultivo:</strong> {{ $program->crop->especie ?? 'N/A' }} &nbsp;|&nbsp;
        <strong>Período:</strong> {{ optional($program->start_date)->format('d/m/Y') }} - {{ optional($program->end_date)->format('d/m/Y') }}
    </div>

    <!-- Tabla -->
    <table>
        <thead>
            <tr>
                <th>Est.</th>
                <th>Nombre<br>Sector</th>
                <th>Año</th>
                <th>SUP</th>
                @foreach($program->fertilizers as $fertilizer)
                    @php
                        $headerQty = max(1, (int) $fertilizer->application_quantity);
                    @endphp
                    <th>{{ $fertilizer->fertilizerMapping->fertilizer_name }}<br>TOTAL</th>
                    <th>Riegos</th>
                    <th>Litros<br>/ riego</th>
                    @for ($i = 1; $i <= $headerQty; $i++)
                        <th>{{ $i }}</th>
                    @endfor
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($program->parcels as $parcel)
                <tr>
                    <td>{{ $parcel->tank->name ?? 'N/A' }}</td>
                    <td>{{ $parcel->name }}</td>
                    <td>{{ optional($program->start_date)->format('Y') }}</td>
                    <td>{{ number_format($parcel->pivot->area, 2) }}</td>

                    @foreach($program->fertilizers as $fertilizer)
                        @php
                            // Cantidad de columnas de aplicaciones definidas por el encabezado
                            $headerQty = max(1, (int) $fertilizer->application_quantity);

                            // Cálculos
                            $area = (float) $parcel->pivot->area;
                            $upa  = (float) $fertilizer->units_per_ha;
                            $df   = (float) ($fertilizer->dilution_factor ?: 1); // evita división por cero

                            // TOTAL = UPA * AREA / DILUTION_FACTOR
                            $fertilizerAmount = ($upa * $area) / $df;

                            // Cantidad sugerida de riegos (puede ajustarse por negocio)
                            $applicationQty = $headerQty;

                            // Litros por riego
                            $litrosRiego = $applicationQty > 0 ? $fertilizerAmount / $applicationQty : $fertilizerAmount;

                            // Si quieres mantener el ajuste mínimo sin desalinear columnas,
                            // solo recalcula litros pero NO cambies $applicationQty ni $headerQty
                            if ($litrosRiego < 150 && $applicationQty > 1) {
                                // ejemplo: en vez de bajar la cantidad, marca litros bajos; mantenemos columnas estables
                                // $litrosRiego = $fertilizerAmount / $applicationQty; // ya calculado
                            }
                        @endphp

                        <td>{{ number_format($fertilizerAmount, 0, ',', '.') }}</td>
                        <td>{{ $applicationQty }}</td>
                        <td>{{ number_format($litrosRiego, 0, ',', '.') }}</td>

                        {{-- Genera EXACTAMENTE tantas celdas como en el encabezado --}}
                        @for ($i = 1; $i <= $headerQty; $i++)
                            <td></td>
                        @endfor
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

</body>
</html>
