<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Arbitros\OfficiatedMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vertical de árbitros: perfil del árbitro (nombre oficial para el
 * auto-matching) y resumen de sus partidos. Solo tenants business_type=referee.
 * Ver docs/arbitros-vertical-spec.md §6.
 */
class RefereeController extends ApiController
{
    /** GET referee/profile */
    public function profile(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        $counts = OfficiatedMatch::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $this->success([
            'referee_name' => data_get($tenant->settings, 'referee_name'),
            'referee_default_fee' => (float) data_get($tenant->settings, 'referee_default_fee', 0),
            'counts' => [
                'pending' => (int) ($counts[OfficiatedMatch::STATUS_PENDING] ?? 0),
                'queued' => (int) ($counts[OfficiatedMatch::STATUS_QUEUED] ?? 0),
                'invoiced' => (int) ($counts[OfficiatedMatch::STATUS_INVOICED] ?? 0),
                'blocked_window' => (int) ($counts[OfficiatedMatch::STATUS_BLOCKED_WINDOW] ?? 0),
            ],
        ]);
    }

    /** PUT referee/profile — nombre oficial (como aparece en la FEF) y valor por defecto. */
    public function updateProfile(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        $data = $request->validate([
            'referee_name' => ['required', 'string', 'min:5', 'max:120'],
            'referee_default_fee' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ]);

        $settings = $tenant->settings ?? [];
        $settings['referee_name'] = trim($data['referee_name']);

        if (array_key_exists('referee_default_fee', $data)) {
            $settings['referee_default_fee'] = (float) ($data['referee_default_fee'] ?? 0);
        }

        $tenant->update(['settings' => $settings]);

        return $this->success([
            'referee_name' => $settings['referee_name'],
            'referee_default_fee' => (float) ($settings['referee_default_fee'] ?? 0),
        ], 'Perfil de árbitro actualizado.');
    }
}
