<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programa de Fertilización - {{ $program->name }}</title>
    <style>
        @page {
            margin: 10mm;
            @bottom-center {
                content: "{{ $program->field->name ?? 'N/A' }} | {{ $program->crop->especie ?? 'N/A' }} | {{ optional($program->start_date)->format('d/m/Y') }} - {{ optional($program->end_date)->format('d/m/Y') }}  —  Página " counter(page) " de " counter(pages);
                font-size: 9px;
                color: #444;
            }
        }
        body {
            font-family: Tahoma, Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        .header img {
            max-width: 42px;
            height: auto;
        }
        .header-info h3 {
            margin: 0;
            font-size: 9px;
        }
        .header-info h4 {
            margin: 0;
            font-size: 8px;
            color: #555;
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
            table-layout: fixed;
        }
        thead { display: table-header-group; }
        th, td {
            border: 1px solid #555;
            padding: 3px;
            text-align: center;
            font-size: 9px;
            line-height: 1.15;
            vertical-align: middle;
            word-break: break-word;
            white-space: normal;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <img src="{{ public_path('img/logogjs.webp') }}" alt="Logo">
        <div class="header-info">
            <h3>Jorge Schmidt y Cia Ltda</h3>
            <h4>Llay Llay, Chile</h4>
        </div>
    </div>

    <h1 class="main-title">Programa de Fertilización: {{ $program->name }}</h1>

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
            {{-- 
                AÑADIMOS ->unique('id') PARA ASEGURAR UNA SOLA FILA POR PARCELA.
                Esto filtra la colección para que solo contenga una entrada por cada ID de parcela único.
                El resto del código ya está preparado para manejar esto correctamente.
            --}}
            @foreach($program->parcels->unique('id')->sortBy('name') as $parcel)
                <tr>
                    <td>{{ $parcel->tank->name ?? 'N/A' }}</td>
                    <td>{{ $parcel->name }}</td>
                    <td>{{ optional($program->start_date)->format('Y') }}</td>
                    <td>{{ number_format($parcel->pivot->area, 2) }}</td>

                    @foreach($program->fertilizers as $fertilizer)
                        @php
                            $headerQty = max(1, (int) $fertilizer->application_quantity);
                            $area = (float) $parcel->pivot->area;
                            $upa  = (float) $fertilizer->units_per_ha;
                            $df   = (float) ($fertilizer->dilution_factor ?: 1);

                            $fertilizerAmount = ($upa * $area) / $df;
                            $applicationQty = $headerQty;
                            $litrosRiego = $applicationQty > 0 ? $fertilizerAmount / $applicationQty : $fertilizerAmount;
                        @endphp
                        <td>{{ number_format($fertilizerAmount, 0, ',', '.') }}</td>
                        <td>{{ $applicationQty }}</td>
                        <td>{{ number_format($litrosRiego, 0, ',', '.') }}</td>
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