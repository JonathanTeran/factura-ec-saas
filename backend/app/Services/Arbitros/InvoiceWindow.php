<?php

namespace App\Services\Arbitros;

use App\Models\Arbitros\OfficiatedMatch;
use Illuminate\Support\Carbon;

/**
 * Ventana de recepción de facturas de la FEF (§5.2 del spec).
 *
 * Regla: los partidos del mes M se facturan a partir del mes M+1, y solo entre
 * el día `start` y `end` (default 1–20, configurable global y por campeonato).
 * Fuera de ventana el partido queda visible como bloqueado; nunca se pierde.
 */
class InvoiceWindow
{
    /** ¿Se puede facturar este partido hoy? */
    public function canInvoice(OfficiatedMatch $match, ?Carbon $today = null): bool
    {
        return $this->evaluate($match, $today)['open'];
    }

    /**
     * Evaluación completa para UI/mensajes.
     *
     * @return array{open: bool, start_day: int, end_day: int, reason: ?string}
     */
    public function evaluate(OfficiatedMatch $match, ?Carbon $today = null): array
    {
        $today ??= Carbon::now();

        $start = $match->championship?->windowStartDay()
            ?? (int) config('arbitros.invoice_window.start_day', 1);
        $end = $match->championship?->windowEndDay()
            ?? (int) config('arbitros.invoice_window.end_day', 20);

        $matchMonth = Carbon::parse($match->match_date)->startOfMonth();
        $currentMonth = $today->copy()->startOfMonth();

        if ($matchMonth->gte($currentMonth)) {
            return [
                'open' => false,
                'start_day' => $start,
                'end_day' => $end,
                'reason' => sprintf(
                    'Este partido es del mes en curso. La FEF lo recibe desde el 1 al %d del próximo mes.',
                    $end
                ),
            ];
        }

        $day = (int) $today->day;

        if ($day < $start || $day > $end) {
            return [
                'open' => false,
                'start_day' => $start,
                'end_day' => $end,
                'reason' => sprintf(
                    'Pasado la fecha: la FEF recibe facturas del %d al %d de cada mes. Se habilitará el próximo periodo.',
                    $start,
                    $end
                ),
            ];
        }

        return ['open' => true, 'start_day' => $start, 'end_day' => $end, 'reason' => null];
    }
}
