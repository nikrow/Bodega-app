<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programas de Fertilización — Consolidado</title>

    <style>
        /* --- Página / pie de página responsivo --- */
        @page {
            margin: 10mm;

            @bottom-center {
                /* Tamaño adaptable del pie: nunca menor a 7px ni mayor a 9px */
                font-size: clamp(7px, 1.2vw, 9px);
                color: #444;
                letter-spacing: 0.1px;

                /* Texto del pie usando las variables del consolidado */
                content: "{{ $tenant->name ?? 'N/A' }} | {{ $cropName ?? 'N/A' }} | {{ \Carbon\Carbon::parse($minDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }}  —  Página " counter(page) " de " counter(pages);
            }
        }

        /* Forzar que se impriman colores de fondo y bordes tal cual */
        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* --- Tipografía base más contenida --- */
        body {
            font-family: Tahoma, Arial, sans-serif;
            font-size: 8.5px; /* un poco menor para ganar aire */
            margin: 0;
            padding: 0;
        }

        /* Encabezado informativo superior más compacto y flexible */
        .info-header {
            margin: 8px 0;
            font-size: clamp(8px, 1.4vw, 10px);
            line-height: 1.25;
        }

        .main-title {
            text-align: center;
            font-size: 11px;
            margin: 4px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* mantenemos fixed para layout estable */
        }

        thead { display: table-header-group; }

        th, td {
            border: 1px solid #555;
            padding: 3px;
            text-align: center;
            font-size: 9px;
            line-height: 1.15;
            vertical-align: middle;

            /* Claves para que NO se corten palabras extraño */
            word-break: keep-all;      /* no rompas palabras */
            overflow-wrap: normal;     /* evita cortes raros */
            hyphens: none;             /* sin guiones automáticos */
            white-space: normal;       /* permite salto solo donde haya espacios/BR */
        }

        th {
            background-color: #f0f0f0 !important; /* asegurar el gris */
            font-weight: bold;
            font-size: 8.5px; /* un pelito más chico para que quepa */
        }

        /* Anchos mínimos para columnas fijas (ajusta a gusto) */
        th.col-est,   td.col-est   { min-width: 28px; }
        th.col-sector,td.col-sector{ min-width: 80px; }
        th.col-sup,   td.col-sup   { min-width: 40px; }

        /* Cabeceras y celdas de fertilizantes algo más compactas */
        th.fert-head, td.fert-cell  { min-width: 60px; }
        th.fert-head               { font-size: 8px; } /* opcional: aún más compacto en headers */
    </style>
</head>

<body>
    <div class="info-header">
        <strong>Reporte Consolidado:</strong> {{ $tenant->name ?? 'N/A' }} <br>
        <strong>Cultivo:</strong> {{ $cropName ?? 'N/A' }} <br>
        @php
            $from = isset($minDate) && $minDate
                ? \Carbon\Carbon::parse($minDate)->format('d/m/Y')
                : 'N/A';

            $to = isset($maxDate) && $maxDate
                ? \Carbon\Carbon::parse($maxDate)->format('d/m/Y')
                : 'N/A';
        @endphp
        <strong>Periodo:</strong> {{ $from }} - {{ $to }}
    </div>

    <h1 class="main-title">Programa Consolidado de Fertilización</h1>

    <table>
        <thead>
            <tr>
                <th class="col-est">Es</th>
                <th class="col-sector">Sector</th>
                <th class="col-sup">Sup</th>

                @foreach ($fertilizers as $fertilizer)
                    @php $headerQty = max(1, (int) $fertilizer->application_quantity); @endphp

                    <th class="fert-head">
                        {{ $fertilizer->fertilizerMapping->fertilizer_name }}
                    </th>
                    <th class="fert-head">Riegos</th>
                    <th class="fert-head">Litros<br>/riego</th>

                    @for ($i = 1; $i <= $headerQty; $i++)
                        <th class="fert-head">{{ $i }}</th>
                    @endfor
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach ($parcels as $parcel)
                <tr>
                    <td class="col-est">{{ $parcel->tank->name ?? 'N/A' }}</td>
                    <td class="col-sector">{{ $parcel->name }}</td>
                    <td>{{ number_format($parcel->pivot->area, 2) }}</td>

                    @foreach ($fertilizers as $fertilizer)
                        @php
                            // Datos desde la matriz consolidada
                            $cellData         = $dataMatrix[$parcel->id][$fertilizer->id] ?? null;
                            $fertilizerAmount = $cellData ? $cellData['amount'] : 0;
                            $applicationQty   = $cellData
                                ? $cellData['application_quantity']
                                : max(1, (int) $fertilizer->application_quantity);

                            // Seguridad extra
                            $applicationQty   = max(1, (int) $applicationQty);

                            $litrosRiego      = $applicationQty > 0
                                ? $fertilizerAmount / $applicationQty
                                : 0;

                            $headerQty        = max(1, (int) $applicationQty);
                        @endphp

                        {{-- Celdas del fertilizante --}}
                        <td class="fert-cell">{{ number_format($fertilizerAmount, 0, ',', '.') }}</td>
                        <td class="fert-cell">{{ $applicationQty }}</td>
                        <td class="fert-cell">{{ number_format($litrosRiego, 0, ',', '.') }}</td>

                        @for ($i = 1; $i <= $headerQty; $i++)
                            <td class="fert-cell"></td> {{-- celdas vacías para registro manual --}}
                        @endfor
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
