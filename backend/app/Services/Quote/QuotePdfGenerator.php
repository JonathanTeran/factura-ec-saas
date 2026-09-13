<?php

namespace App\Services\Quote;

use App\Models\Tenant\Quote;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF de la proforma (plantilla propia, no es un RIDE: no lleva clave de
 * acceso ni autorización porque no es un comprobante tributario).
 */
class QuotePdfGenerator
{
    public function render(Quote $quote): string
    {
        $quote->loadMissing(['company', 'customer', 'items.product']);

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $quote->company,
            'customer' => $quote->customer,
            'items' => $quote->items,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    public function filename(Quote $quote): string
    {
        return 'cotizacion-'.preg_replace('/[^A-Za-z0-9\-_]/', '', $quote->quote_number).'.pdf';
    }
}
