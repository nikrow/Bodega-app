<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programas de Fertilización — Consolidado</title>

    <style>
        /* --- Página / pie de página --- */
        @page {
            margin: 10mm;
            @bottom-center {
                content: "{{ $tenant->name ?? 'N/A' }} | {{ $cropName ?? 'N/A' }} | {{ \Carbon\Carbon::parse($minDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }}  —  Página " counter(page) " de " counter(pages);
                font-size: 9px;
                color: #444;
                letter-spacing: 0.1px;
            }
        }

        /* Respetar colores en impresión (Browsershot/Chromium) */
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* Tipografía compacta para muchas columnas */
        body {
            font-family: Tahoma, Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }

        .info-header {
            margin: 8px 10px 4px 10px;
            font-size: 9px;
            line-height: 1.25;
        }

        .main-title {
            text-align: center;
            font-size: 11px;
            margin: 4px 10px 6px 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* layout estable */
        }

        thead { display: table-header-group; } /* repetir encabezado por página */

        th, td {
            border: 1px solid #555;   /* líneas más oscuras */
            padding: 3px;
            text-align: center;
            font-size: 9px;
            line-height: 1.15;
            vertical-align: middle;

            /* evitar cortes raros */
            word-break: keep-all;
            overflow-wrap: normal;
            hyphens: none;
            white-space: normal;
        }

        th {
            background-color: #f0f0f0 !important;
            font-weight: bold;
            font-size: 8.5px;
        }

        /* Anchos mínimos como en tu formato original */
        th.col-est,   td.col-est    { min-width: 32px; }
        th.col-sector,td.col-sector { min-width: 90px; text-align: left; }
        th.col-sup,   td.col-sup    { min-width: 48px; }

        /* Cabeceras/celdas de fertilizantes */
        th.fert-head, td.fert-cell { min-width: 60px; }
    </style>
</head>

<body>
    <div class="info-header">
        <strong>Reporte Consolidado:</strong> {{ $tenant->name ?? 'N/A' }}<br>
        <strong>Cultivo:</strong> {{ $cropName ?? 'N/A' }}<br>
        @php
            $from = isset($minDate) && $minDate ? \Carbon\Carbon::parse($minDate)->format('d/m/Y') : 'N/A';
            $to   = isset($maxDate) && $maxDate ? \Carbon\Carbon::parse($maxDate)->format('d/m/Y') : 'N/A';
        @endphp
        <strong>Periodo:</strong> {{ $from }} - {{ $to }}
    </div>

    <h1 class="main-title">Programa Consolidado de Fertilización</h1>

    @php
        /**
         * Consolidadores que respetan el FORMATO de tu tabla:
         * - total por fertilizante (suma global)
         * - riegos consolidados (suma de riegos)
         * - litros/ riego = total / riegos (si riegos > 0)
         *
         * Soporta varias estructuras en $dataMatrix[$parcelId][$fertilizerId]:
         *   - número directo
         *   - ['amount' => x, 'riegos' => y]
         *   - ['amounts' => [x1, x2, ...], 'riegos' => [y1, y2, ...]]
         *   - ['lines' => [ ['amount'=>x,'riegos'=>y], ...]]
         *   - mezclas: suma todo lo que sea numérico plausible
         */
        $sumAmounts = function($cell) {
            if (is_null($cell)) return 0;

            if (is_numeric($cell)) return (float)$cell;

            $sum = 0;
            if (is_array($cell)) {
                if (array_key_exists('amount', $cell) && is_numeric($cell['amount'])) {
                    $sum += (float)$cell['amount'];
                }
                if (array_key_exists('amounts', $cell) && is_array($cell['amounts'])) {
                    $sum += collect($cell['amounts'])->filter('is_numeric')->sum();
                }
                if (array_key_exists('lines', $cell) && is_array($cell['lines'])) {
                    $sum += collect($cell['lines'])->sum(function($ln){
                        if (is_numeric($ln)) return (float)$ln;
                        if (is_array($ln) && array_key_exists('amount',$ln) && is_numeric($ln['amount'])) {
                            return (float)$ln['amount'];
                        }
                        return 0;
                    });
                }
                // cualquier otro campo que contenga amount numérico
                foreach ($cell as $v) {
                    if (is_numeric($v)) $sum += (float)$v;
                    if (is_array($v) && array_key_exists('amount',$v) && is_numeric($v['amount'])) {
                        $sum += (float)$v['amount'];
                    }
                }
            }
            return $sum;
        };

        $sumRiegos = function($cell, $fallbackHeaderQty = 1) {
            if (is_null($cell)) return (int)$fallbackHeaderQty;

            $sum = 0; $found = false;

            if (is_array($cell)) {
                if (array_key_exists('riegos',$cell) && is_numeric($cell['riegos'])) {
                    $sum += (int)$cell['riegos']; $found = true;
                }
                if (array_key_exists('riegos',$cell) && is_array($cell['riegos'])) {
                    $sum += collect($cell['riegos'])->filter('is_numeric')->sum(); $found = true;
                }
                if (array_key_exists('lines',$cell) && is_array($cell['lines'])) {
                    $sum += collect($cell['lines'])->sum(function($ln){
                        if (is_array($ln) && array_key_exists('riegos',$ln) && is_numeric($ln['riegos'])) {
                            return (int)$ln['riegos'];
                        }
                        return 0;
                    });
                    $found = true;
                }
            }

            // Si no encontramos info explícita de riegos, usa el header como fallback (mantiene formato)
            if (!$found) $sum = max(1, (int)$fallbackHeaderQty);

            return max(1, (int)$sum);
        };
    @endphp

    <table>
        <thead>
            <tr>
                <th class="col-est">Es</th>
                <th class="col-sector">Sector</th>
                <th class="col-sup">Sup</th>

                {{-- MISMO FORMATO: por cada fertilizante => TOTAL | Riegos | Litros/riego | 1..N --}}
                @foreach ($fertilizers as $fertilizer)
                    @php $headerQty = max(1, (int)$fertilizer->application_quantity); @endphp

                    <th class="fert-head">{{ $fertilizer->fertilizerMapping->fertilizer_name }}</th>
                    <th class="fert-head">Riegos</th>
                    <th class="fert-head">Litros<br>/riego</th>

                    @for ($i = 1; $i <= $headerQty; $i++)
                        <th class="fert-head">{{ $i }}</th>
                    @endfor
                @endforeach
            </tr>
        </thead>

        <tbody>
            {{-- Orden alfabético por nombre del sector (sin cambiar formato) --}}
            @foreach (($parcels ?? collect())->sortBy('name') as $parcel)
                <tr>
                    <td class="col-est">{{ $parcel->tank->name ?? 'N/A' }}</td>
                    <td class="col-sector">{{ $parcel->name }}</td>
                    <td class="col-sup">{{ number_format($parcel->pivot->area ?? 0, 2, ',', '.') }}</td>

                    @foreach ($fertilizers as $fertilizer)
                        @php
                            $headerQty = max(1, (int)$fertilizer->application_quantity);

                            // Consolidar desde la matriz original (no cambiamos estructura de columnas)
                            $cell = $dataMatrix[$parcel->id][$fertilizer->id] ?? null;

                            $totalConsolidado  = $sumAmounts($cell);
                            $riegosConsolidados = $sumRiegos($cell, $headerQty);

                            $litrosRiego = $riegosConsolidados > 0
                                ? $totalConsolidado / $riegosConsolidados
                                : 0;
                        @endphp

                        <td class="fert-cell">{{ number_format($totalConsolidado, 0, ',', '.') }}</td>
                        <td class="fert-cell">{{ $riegosConsolidados }}</td>
                        <td class="fert-cell">{{ number_format($litrosRiego, 0, ',', '.') }}</td>

                        {{-- Mantener EXACTAMENTE la cantidad de columnas 1..N del HEADER --}}
                        @for ($i = 1; $i <= $headerQty; $i++)
                            <td class="fert-cell"></td>
                        @endfor
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
