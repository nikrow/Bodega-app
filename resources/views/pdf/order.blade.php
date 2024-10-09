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
    <div class="header" border="1">
        <img src="{{ public_path('img/logo-js.png') }}" alt="logo">
        <h1>Orden de Aplicación N° {{ $order->orderNumber }}</h1>
    </div>
    <div>
        <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <p><strong>Campo:</strong> {{ $order->field->name ?? 'N/A' }}</p>
    <p><strong>Encargado:</strong> {{ $order->user->name ?? 'N/A' }}</p>
    <p><strong>Cultivo:</strong> {{ $order->crop->especie ?? 'N/A' }}</p>
    <p><strong>Estado:</strong> {{ $order->status->value ?? 'N/A' }}</p>

    <h2>Cuarteles</h2>
    @if ($order->orderAplications->isNotEmpty())
        <ul>
            @foreach ($order->orderAplications as $aplication)
                @if ($aplication->parcel)
                    <li>{{ $aplication->parcel->name }}</li>
                @endif
            @endforeach
        </ul>
    @else
        <p>No hay cuarteles aplicados.</p>
    @endif

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
                    <td>{{ $line->reasons ?? 'N/A' }}</td>
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
