<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Arbitros\Championship;
use App\Models\Arbitros\Club;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Services\Arbitros\InvoiceWindow;
use App\Services\Arbitros\RefereeInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Vertical de árbitros: perfil, partidos pitados (pendientes por facturar),
 * registro manual, facturación 1×1 y catálogos para los selectores.
 * Solo tenants business_type=referee. Ver docs/arbitros-vertical-spec.md §4.
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

    /** GET referee/matches — partidos del árbitro con estado de ventana. */
    public function matches(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        $request->validate([
            'status' => ['nullable', 'string'],
        ]);

        $query = OfficiatedMatch::query()
            ->with(['championship:id,name', 'homeClub:id,name', 'awayClub:id,name', 'document:id,status,sequential,series,access_key'])
            ->orderByDesc('match_date')
            ->orderByDesc('id');

        if ($status = $request->string('status')->toString()) {
            $statuses = array_intersect(
                explode(',', $status),
                [
                    OfficiatedMatch::STATUS_PENDING,
                    OfficiatedMatch::STATUS_QUEUED,
                    OfficiatedMatch::STATUS_INVOICED,
                    OfficiatedMatch::STATUS_BLOCKED_WINDOW,
                ]
            );
            if ($statuses !== []) {
                $query->whereIn('status', $statuses);
            }
        }

        $window = app(InvoiceWindow::class);
        $today = now();

        $rows = $query->get()->map(function (OfficiatedMatch $m) use ($window) {
            $eval = $window->evaluate($m);

            return [
                'id' => $m->id,
                'match_date' => $m->match_date->toDateString(),
                'championship_id' => $m->championship_id,
                'championship' => $m->championship?->name,
                'home_club' => $m->homeClub?->name,
                'away_club' => $m->awayClub?->name,
                'role' => $m->role,
                'fee' => (float) $m->fee,
                'status' => $m->status,
                'source' => $m->source,
                'notes' => $m->notes,
                'invoiced_at' => $m->invoiced_at?->toIso8601String(),
                'document' => $m->document ? [
                    'id' => $m->document->id,
                    'status' => $m->document->status,
                    'number' => $m->document->series . '-' . $m->document->sequential,
                ] : null,
                'window' => $eval,
            ];
        });

        $start = (int) config('arbitros.invoice_window.start_day', 1);
        $end = (int) config('arbitros.invoice_window.end_day', 20);

        return $this->success([
            'matches' => $rows,
            'window' => [
                'today' => $today->toDateString(),
                'start_day' => $start,
                'end_day' => $end,
                'open_today' => $today->day >= $start && $today->day <= $end,
            ],
        ]);
    }

    /** POST referee/matches — registro manual de un partido pitado (§4.2). */
    public function storeMatch(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        $data = $request->validate([
            // Campeonato SIEMPRE del catálogo (§5.5).
            'championship_id' => ['required', Rule::exists('championships', 'id')->where('is_active', true)],
            'home_club_id' => ['required', 'different:away_club_id', Rule::exists('clubs', 'id')],
            'away_club_id' => ['required', Rule::exists('clubs', 'id')],
            'match_date' => ['required', 'date', 'before_or_equal:today'],
            'role' => ['required', Rule::in([
                OfficiatedMatch::ROLE_ARBITRO,
                OfficiatedMatch::ROLE_ASISTENTE_1,
                OfficiatedMatch::ROLE_ASISTENTE_2,
                OfficiatedMatch::ROLE_CUARTO,
                OfficiatedMatch::ROLE_VAR,
                OfficiatedMatch::ROLE_COMISARIO,
                OfficiatedMatch::ROLE_DELEGADO,
            ])],
            'fee' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $match = OfficiatedMatch::create($data + [
            'tenant_id' => $tenant->id,
            'status' => OfficiatedMatch::STATUS_PENDING,
            'source' => 'manual',
        ]);

        return $this->success(['id' => $match->id], 'Partido registrado como pendiente por facturar.', 201);
    }

    /** PUT referee/matches/{officiatedMatch} — editar valor/rol/notas (solo pendientes). */
    public function updateMatch(Request $request, OfficiatedMatch $officiatedMatch): JsonResponse
    {
        if (! in_array($officiatedMatch->status, [OfficiatedMatch::STATUS_PENDING, OfficiatedMatch::STATUS_BLOCKED_WINDOW], true)) {
            return $this->error('Solo se pueden editar partidos pendientes por facturar.', 400);
        }

        $data = $request->validate([
            'fee' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'role' => ['nullable', Rule::in([
                OfficiatedMatch::ROLE_ARBITRO,
                OfficiatedMatch::ROLE_ASISTENTE_1,
                OfficiatedMatch::ROLE_ASISTENTE_2,
                OfficiatedMatch::ROLE_CUARTO,
                OfficiatedMatch::ROLE_VAR,
                OfficiatedMatch::ROLE_COMISARIO,
                OfficiatedMatch::ROLE_DELEGADO,
            ])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $officiatedMatch->update(array_filter($data, fn ($v) => $v !== null));

        return $this->success(['id' => $officiatedMatch->id], 'Partido actualizado.');
    }

    /** DELETE referee/matches/{officiatedMatch} — descartar propuesta (solo pendientes). */
    public function destroyMatch(Request $request, OfficiatedMatch $officiatedMatch): JsonResponse
    {
        if (! in_array($officiatedMatch->status, [OfficiatedMatch::STATUS_PENDING, OfficiatedMatch::STATUS_BLOCKED_WINDOW], true)) {
            return $this->error('Solo se pueden eliminar partidos pendientes por facturar.', 400);
        }

        $officiatedMatch->delete();

        return $this->success(null, 'Partido eliminado.');
    }

    /** POST referee/matches/invoice — factura 1×1 los seleccionados (§4.4). */
    public function invoice(Request $request, RefereeInvoiceService $service): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['integer'],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('tenant_id', $tenant->id)],
            'emission_point_id' => ['nullable', Rule::exists('emission_points', 'id')->where('tenant_id', $tenant->id)],
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $emissionPoint = isset($data['emission_point_id'])
            ? EmissionPoint::find($data['emission_point_id'])
            : null;

        try {
            $results = $service->invoiceBatch(
                $tenant,
                array_map('intval', $data['ids']),
                $customer,
                $request->user(),
                $emissionPoint
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $summary = collect($results)->countBy('status');

        return $this->success([
            'results' => $results,
            'summary' => [
                'queued' => (int) ($summary['queued'] ?? 0),
                'draft' => (int) ($summary['draft'] ?? 0),
                'blocked_window' => (int) ($summary['blocked_window'] ?? 0),
                'skipped' => (int) ($summary['skipped'] ?? 0),
                'error' => (int) ($summary['error'] ?? 0),
            ],
        ], 'Proceso de facturación completado.');
    }

    /** GET referee/championships — catálogo para el selector (solo activos). */
    public function championships(): JsonResponse
    {
        return $this->success([
            'championships' => Championship::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'category', 'season']),
        ]);
    }

    /** GET referee/clubs?search= — catálogo para el selector. */
    public function clubs(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());

        return $this->success([
            'clubs' => Club::query()
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'name']),
        ]);
    }
}
