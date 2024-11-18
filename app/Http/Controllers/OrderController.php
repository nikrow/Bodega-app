<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

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

        // Generar el PDF
        $pdf = DomPDF::loadView('pdf.order', compact('order'));

        // Descargar el PDF
        return $pdf->download('orden_' . $order->orderNumber . '.pdf');
    }
}
