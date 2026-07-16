<?php

namespace App\Services\Arbitros;

use App\Models\Arbitros\OfficiatedMatch;
use Illuminate\Support\Carbon;

/**
 * Ventana de recepción de facturas de la FEF (§5.2 del spec).
 *
 * Regla real (confirmada con la FEF): la FEF recibe facturas SOLO entre el día
 * `start` y `end` de cada mes (default 1–20, configurable global y por
 * campeonato). Si hoy está dentro de esa ventana, el árbitro puede facturar sus
 * partidos pendientes (de este mes o de meses anteriores). Si hoy está fuera
 * (después del día `end`), la FEF no recibe: se espera al día `start` del
 * próximo periodo. Fuera de ventana el partido nunca se pierde, solo espera.
 */
class InvoiceWindow
{
    /** ¿Se puede facturar hoy? */
    public function canInvoice(?OfficiatedMatch $match = null, ?Carbon $today = null): bool
    {
        return $this->evaluate($match, $today)['open'];
    }

    /**
     * Evaluación completa para UI/mensajes.
     *
     * @return array{open: bool, start_day: int, end_day: int, reason: ?string}
     */
    public function evaluate(?OfficiatedMatch $match = null, ?Carbon $today = null): array
    {
        $today ??= Carbon::now();

        $start = $match?->championship?->windowStartDay()
            ?? (int) config('arbitros.invoice_window.start_day', 1);
        $end = $match?->championship?->windowEndDay()
            ?? (int) config('arbitros.invoice_window.end_day', 20);

        // Guard: un override inválido (p.ej. start > end) dejaría un campeonato
        // imposible de facturar. Se cae al default global de config.
        if ($start < 1 || $end < 1 || $end > 31 || $start > $end) {
            $start = (int) config('arbitros.invoice_window.start_day', 1);
            $end = (int) config('arbitros.invoice_window.end_day', 20);
        }

        $day = (int) $today->day;

        if ($day < $start || $day > $end) {
            return [
                'open' => false,
                'start_day' => $start,
                'end_day' => $end,
                'reason' => sprintf(
                    'La FEF recibe facturas del %d al %d de cada mes. Se habilitará el día %d del próximo periodo.',
                    $start,
                    $end,
                    $start
                ),
            ];
        }

        return ['open' => true, 'start_day' => $start, 'end_day' => $end, 'reason' => null];
    }
}
