<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Orden de Aplicación</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;600&display=swap');

        @page {
            margin: 15mm;
            @bottom-center {
                content: "Orden N° {{ $order->orderNumber }} - Página " counter(page) " de " counter(pages);
                font-size: 10px;
                color: #555;
            }
        }

        body {
            font-family: 'Manrope', sans-serif; /* Cambiado a Manrope */
            font-size: 12px;
            line-height: 1.2;
            color: #333;
            margin: 0;
            padding: 0;
            position: relative;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 10px;
            background: #fff;
        }

        /* HEADER */
        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .header img {
            max-width: 70px;
            height: auto;
        }

        .header-info h3 {
            margin: 0;
            font-size: 12px;
        }

        .header-info h4 {
            margin: 0;
            font-size: 10px;
            color: #555;
        }

        /* TITULO PRINCIPAL */
        .main-title {
            text-align: center;
            font-size: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        /* SECCIONES */
        .box {
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            background-color: #fff;
        }

        .section-title-bar {
            background-color: #f5f5f5;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        /* DETALLES */
        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 15px;
            margin-bottom: 15px;
        }

        .details ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .details li {
            margin-bottom: 3px;
        }

        /* GRID DE GRUPOS */
        .grid-list {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 5px;
            margin-top: 5px;
        }

        .grid-item {
            padding: 6px;
            font-size: 11px;
            background: #fafafa;
            border-radius: 3px;
            text-align: center;
            text-transform: capitalize;
        }

        /* CUARTELES */
        .cuarteles-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .cuartel-item {
            padding: 6px;
            font-size: 11px;
            text-align: left;
        }

        /* IMPLEMENTOS */
        .implementos-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .implementos-section li {
            padding: 6px 0;
            font-size: 11px;
        }

        /* TABLAS */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 6px;
            text-align: left;
            font-size: 11px;
        }

        th {
            background-color: #f9f9f9;
        }

        /* SALTO DE PÁGINA */
        .page-break {
            page-break-before: always;
        }

        /* FIRMA */
        .signature {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 150px;
            margin: 30px auto 10px;
        }

        .signature-name {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <img src="{{ base_path('public/img/logogjs.webp') }}" alt="Logo">
        <div class="header-info">
            <h3>Jorge Schmidt y Cia Ltda</h3>
            <h4>Llay Llay, Chile</h4>
        </div>
    </div>

    <!-- TITULO PRINCIPAL -->
    <h1 class="main-title">Orden de Aplicación N° {{ $order->orderNumber }}</h1>

    <!-- CAMPO -->
    <div class="box">
        <div class="section-title-bar">Campo</div>
        <h3>{{ $order->field->name ?? 'N/A' }}</h3>
    </div>

    <!-- DETALLES -->
    <div class="box">
        <div class="section-title-bar">Detalles</div>
        <div class="details">
            <ul>
                <li><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</li>
                <li><strong>Responsable técnico:</strong> {{ $order->user->name ?? 'N/A' }}</li>
            </ul>
            <ul>
                <li><strong>Cultivo:</strong> {{ $order->crop->especie ?? 'N/A' }}</li>
                <li><strong>Objetivo:</strong> {{ $order->objective ?? 'N/A' }}</li>
                <li><strong>Indicaciones:</strong> {{ $order->indications}}</li>
            </ul>
        </div>
    </div>

    <!-- GRUPOS -->
    <div class="box">
        <div class="section-title-bar">Grupos</div>
        <div class="grid-list">
            @foreach ($order->family as $family)
                <div class="grid-item">{{ $family }}</div>
            @endforeach
        </div>
    </div>

    <!-- PRODUCTOS -->
    <div class="box">
        <div class="section-title-bar">Productos</div>
        <p><strong>Mojamiento:</strong> {{ number_format($order->wetting, 2) }} l/ha</p>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Ingrediente activo</th>
                <th>Dosis</th>
                <th>Carencia</th>
                <th>Reingreso</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($order->orderLines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->product->product_name }}</td>
                    <td>{{ $line->product->active_ingredients ?? 'N/A' }}</td>
                    <td>{{ $line->dosis }} l/100l</td>
                    <td>{{ $line->waiting_time ?? 'N/A' }}</td>
                    <td>{{ $line->reentry ?? 'N/A' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- CUARTELES -->
    <div class="page-break"></div>
    <div class="box">
        <div class="section-title-bar">Cuarteles</div>
        <div class="cuarteles-list">
            @foreach ($order->parcels as $index => $parcel)
                <div class="cuartel-item">{{ $index + 1 }}. {{ $parcel->name }} - {{ $parcel->surface }} ha</div>
            @endforeach
        </div>
        <p><strong>Superficie total:</strong> {{ number_format($order->total_area, 2) }} ha</p>
    </div>

    <!-- IMPLEMENTOS -->
    <div class="box">
        <div class="section-title-bar">Implementos</div>
        <div class="implementos-section">
            <h3>EPP</h3>
            <ul>
                @foreach ($order->epp as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>

            <h3>Equipamiento</h3>
            <ul>
                @foreach ($order->equipment as $equip)
                    <li>{{ $equip }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- FIRMA -->
    <div class="signature">
        <div class="signature-line"></div>
        <div class="signature-name">{{ $order->user->name ?? '________________' }}</div>
        <div>Responsable técnico</div>
    </div>
</div>
</body>
</html>
