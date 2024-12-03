<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Orden de Aplicación</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: #333;
        }
        h1, h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        div {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header img {
            max-width: 150px;
        }
        .header h1 {
            margin-left: 20px;
        }
    </style>
</head>
<body>
<div class="box">
    <div class="header">
        <!-- SVG Logo -->
        ImageSVG($file='img/lgovector2.svg', $x=15, $y=30, $w='', $h='', $link='http://www.tcpdf.org', $align='',
        $palign='', $border=1, $fitonpage=false);
        <h2>Orden de Aplicación N° {{ $order->orderNumber }}</h2>
    </div>
    <div class="details">
        <ul>
            <li><strong>Responsable técnico:</strong> {{ $order->user->name ?? 'N/A' }}</li>
            <li><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</li>
            <li><strong>Campo:</strong> {{ $order->field->name ?? 'N/A' }}</li>
            <li><strong>Encargado:</strong> {{ $order->updatedBy->name ?? 'N/A' }}</li>
            <li><strong>Cultivo:</strong> {{ $order->crop->especie ?? 'N/A' }}</li>
            <li><strong>Mojamiento:</strong> {{ number_format($order->wetting, 2) }} l/ha</li>
            <li><strong>Objetivo:</strong> {{ $order->objective ?? 'N/A' }}</li>
            <li><strong>Equipamiento:</strong> {{ $order->equipment ?? 'N/A' }}</li>
        </ul>
    </div>
</div>

<!-- Sección de Cuarteles -->
<div class="box">
    <h2>Cuarteles</h2>
    @if ($order->parcels->isNotEmpty())
        <table>
            <thead>
            <tr>
                <th>Cuartel</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($order->parcels as $parcel)
                <tr>
                    <td>{{ $parcel->name }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <li><strong>Superficie total:</strong> {{ number_format($order->total_area, 2) }} ha</li>
    @else
        <p>No hay cuarteles aplicados.</p>
    @endif
</div>
<div class="box">
    <h2>Elementos de protección personal</h2>
    <table>
        <thead>
        <tr>
            <th>EPP</th>
        </tr>
        </thead>
        <tbody>
        @php
            $maxCount = count($order->epp);
        @endphp

        @for ($i = 0; $i < $maxCount; $i++)
            <tr>
                <td>{{ $order->epp[$i] ?? '' }}</td>
            </tr>
        @endfor
        </tbody>
    </table>
</div>

<div class="box">
    <h2>Productos</h2>
    @if ($order->orderLines->isNotEmpty())
        <table>
            <thead>
            <tr>
                <th>Producto</th>
                <th>Ingrediente activo</th>
                <th>Dosis</th>
                <th>Razón</th>
                <th>Carencia</th>
                <th>Reingreso</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($order->orderLines as $line)
                <tr>
                    <td>{{ $line->product->product_name }}</td>
                    <td>{{ $line->product->active_ingredients ?? 'N/A' }}</td>
                    <td>{{ $line->dosis }} l/100l</td>
                    <td>{{ $line->waiting_time ?? 'N/A' }}</td>
                    <td>{{ $line->reentry ?? 'N/A' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p>No hay productos registrados.</p>
    @endif
</div>
</body>
</html>
