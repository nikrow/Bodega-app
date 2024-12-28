<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Spatie\Browsershot\Browsershot;

class OrderController extends Controller
{
    public function downloadPdf(Order $order)
    {
        // Cargar las relaciones necesarias
        $order->load([
            'field',
            'user',
            'crop',
            'orderApplications.parcel',
            'orderLines.product',
        ]);

        // Renderizar la vista a HTML
        $html = view('pdf.order', compact('order'))->render();

        // Generar el PDF con Browsershot
        $pdf = Browsershot::html($html)
            ->format('letter')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->pdf();

        // Retornar el PDF al navegador
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="orden_' . $order->orderNumber . '.pdf"');
    }
    public function bodegaPdf(Order $order)
    {
        // Cargar las relaciones necesarias
        $order->load([
            'field',
            'user',
            'crop',
            'orderApplications.parcel',
            'orderLines.product',
        ]);

        // Renderizar la vista a HTML
        $html = view('pdf.orderBodega', compact('order'))->render();

        // Generar el PDF con Browsershot
        $pdf = Browsershot::html($html)
            ->format('letter')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->pdf();

        // Retornar el PDF al navegador
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="orden_' . $order->orderNumber . '.pdf"');
    }
}
