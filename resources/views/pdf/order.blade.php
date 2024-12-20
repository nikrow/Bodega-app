<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Orden de Aplicación</title>

    <style>
        @page {
            margin: 15mm;
            @bottom-center {
                content: "Orden N° {{ $order->orderNumber }} - Página " counter(page) " de " counter(pages);
                font-size: 10px;
                color: #555;
            }
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px; /* Fuente más pequeña */
            line-height: 1.2; /* Espaciado reducido */
            color: #333;
            margin: 0;
            padding: 0;
            position: relative;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 10px; /* Padding reducido */
            position: relative;
            background: #fff;
        }

        /* HEADER */
        .header {
            display: flex;
            align-items: center;
            gap: 15px; /* Espaciado más pequeño */
            margin-bottom: 10px;
        }

        .header img {
            max-width: 70px; /* Imagen más pequeña */
            height: auto;
        }

        .header-info {
            line-height: 1.1;
        }

        .header-info h3 {
            margin: 0;
            font-size: 12px;
        }

        .header-info h4 {
            margin: 0;
            font-size: 10px;
            font-weight: normal;
            color: #555;
        }

        /* TITULO PRINCIPAL */
        .main-title {
            text-align: center;
            font-size: 20px; /* Fuente más pequeña */
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        /* SECCIONES */
        .box {
            background-color: #fff;
            margin-bottom: 10px; /* Espaciado reducido */
            padding: 15px; /* Padding reducido */
            border-radius: 5px;
        }

        /* TÍTULO CON BARRA */
        .section-title-bar {
            background-color: #f5f5f5;
            padding: 8px 10px; /* Padding reducido */
            font-size: 14px; /* Fuente más pequeña */
            font-weight: bold;
            margin-bottom: 10px; /* Espaciado reducido */
            border-radius: 5px;
        }

        /* DETALLES */
        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 15px; /* Espaciado reducido */
            margin-bottom: 15px;
        }

        .details ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .details li {
            margin-bottom: 3px; /* Espaciado reducido */
        }

        /* TABLAS Y LISTADOS */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px; /* Espaciado reducido */
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 6px; /* Padding reducido */
            text-align: left;
            font-size: 11px; /* Fuente más pequeña */
        }

        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        /* GRID DE ELEMENTOS */
        .grid-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1.5fr)); /* Columnas más compactas */
            gap: 5px;
            margin-top: 5px; /* Espaciado reducido */
        }

        .grid-item {
            border: 1px solid #ddd;
            padding: 6px; /* Padding reducido */
            font-size: 11px; /* Fuente más pequeña */
            background: #fafafa;
            border-radius: 3px;
            text-align: center;
            word-wrap: break-word;
            overflow: hidden;
        }

        /* IMPLEMENTOS */
        .implementos-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); /* Más compacto */
            gap: 5px;
            margin-top: 5px;
        }

        .implementos-item {
            border: 1px solid #ddd;
            padding: 4px 6px; /* Padding reducido */
            font-size: 11px; /* Fuente más pequeña */
            background: #fafafa;
            border-radius: 3px;
            text-align: center;
        }

        /* FIRMA */
        .signature {
            margin-top: 30px; /* Espaciado reducido */
            text-align: center;
            font-size: 12px; /* Fuente más pequeña */
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 150px; /* Más compacto */
            margin: 30px auto 10px;
        }

        .signature-name {
            font-weight: bold;
        }

        /* Salto de página */
        .page-break {
            page-break-before: always;
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
                <th>Producto</th>
                <th>Ingrediente activo</th>
                <th>Dosis</th>
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
    </div>

    <!-- SALTO DE PÁGINA Y CUARTELES -->
    <div class="page-break"></div>
    <div class="box">
        <div class="section-title-bar">Cuarteles</div>
        <div class="grid-list">
            @foreach ($order->parcels as $parcel)
                <div class="grid-item">{{ $parcel->name }}</div>
            @endforeach
        </div>
        <p><strong>Superficie total:</strong> {{ number_format($order->total_area, 2) }} ha</p>
    </div>

    <!-- IMPLEMENTOS -->
    <div class="box">
        <div class="section-title-bar">Implementos</div>
        <h3>EPP</h3>
        <div class="implementos-list">
            @foreach ($order->epp as $item)
                <div class="implementos-item">{{ $item }}</div>
            @endforeach
        </div>

        <h3 style="margin-top: 10px;">Equipamiento</h3>
        <div class="implementos-list">
            @foreach ($order->equipment as $equip)
                <div class="implementos-item">{{ $equip }}</div>
            @endforeach
        </div>
    </div>

    <!-- FIRMA -->
    <div class="signature">
        <div class="signature-line"></div>
        <div class="signature-name">{{ $order->updatedBy->name ?? '________________' }}</div>
        <div>Responsable técnico</div>
    </div>
</div>
</body>
</html>
