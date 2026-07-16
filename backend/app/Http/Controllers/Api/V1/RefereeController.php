<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentStatus;
use App\Models\Arbitros\CatalogRequest;
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

        $pending = (int) ($counts[OfficiatedMatch::STATUS_PENDING] ?? 0)
            + (int) ($counts[OfficiatedMatch::STATUS_BLOCKED_WINDOW] ?? 0);

        // "Fuera de ventana" es una condición viva: si HOY la FEF no recibe,
        // todos los pendientes están esperando el próximo periodo.
        $windowOpenToday = app(InvoiceWindow::class)->canInvoice();

        return $this->success([
            'referee_name' => data_get($tenant->settings, 'referee_name'),
            'referee_default_fee' => (float) data_get($tenant->settings, 'referee_default_fee', 0),
            'counts' => [
                'pending' => $pending,
                'queued' => (int) ($counts[OfficiatedMatch::STATUS_QUEUED] ?? 0),
                'invoiced' => (int) ($counts[OfficiatedMatch::STATUS_INVOICED] ?? 0),
                'blocked_window' => $windowOpenToday ? 0 : $pending,
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
            ->with(['championship:id,name', 'homeClub:id,name,city', 'awayClub:id,name,city', 'document:id,status,sequential,series,access_key'])
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
                'home_club_city' => $m->homeClub?->city,
                'away_club' => $m->awayClub?->name,
                'away_club_city' => $m->awayClub?->city,
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

        $tenantId = $tenant->id;
        // Campeonato/clubes del catálogo VISIBLE: oficiales activos + los
        // personales del propio árbitro (§5.5).
        $visibleChampionship = fn ($q) => $q
            ->where(fn ($g) => $g->whereNull('tenant_id')->where('is_active', true))
            ->orWhere('tenant_id', $tenantId);
        $visibleClub = fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);

        $data = $request->validate([
            'championship_id' => ['required', Rule::exists('championships', 'id')->where($visibleChampionship)],
            'home_club_id' => ['required', 'different:away_club_id', Rule::exists('clubs', 'id')->where($visibleClub)],
            'away_club_id' => ['required', Rule::exists('clubs', 'id')->where($visibleClub)],
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

    /**
     * POST referee/matches/{match}/reactivate — anula la factura del partido y
     * lo devuelve a pendiente para volver a facturarlo (§4.5).
     *
     * Si la factura está AUTORIZADA, se anula (el observer reactiva el partido).
     * Si quedó en borrador/en cola/rechazada, se libera directamente. Nunca deja
     * el partido atascado.
     */
    public function reactivate(Request $request, OfficiatedMatch $officiatedMatch): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        if (! in_array($officiatedMatch->status, [OfficiatedMatch::STATUS_QUEUED, OfficiatedMatch::STATUS_INVOICED], true)) {
            return $this->error('Solo se puede anular un partido que está facturado o en proceso.', 400);
        }

        $document = $officiatedMatch->document;

        if ($document && $document->status === DocumentStatus::AUTHORIZED) {
            // Anular la factura autorizada → el DocumentStatusObserver reactiva.
            $document->update([
                'status' => DocumentStatus::VOIDED,
                'voided_at' => now(),
                'void_reason' => 'Anulada por el árbitro.',
            ]);
        } else {
            // Borrador / en cola / rechazada / sin doc: liberar directamente.
            $officiatedMatch->update([
                'status' => OfficiatedMatch::STATUS_PENDING,
                'electronic_document_id' => null,
                'invoiced_at' => null,
            ]);
        }

        return $this->success(
            ['id' => $officiatedMatch->id],
            'Partido reactivado. Ya puedes volver a facturarlo.'
        );
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

    /** GET referee/catalog-requests — mis solicitudes (para mostrar estado). */
    public function catalogRequests(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        return $this->success([
            'requests' => CatalogRequest::query()
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'type', 'name', 'status', 'resolution_note', 'created_at']),
        ]);
    }

    /** POST referee/catalog-requests — pedir un campeonato/club faltante (§5.5). */
    public function storeCatalogRequest(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        $data = $request->validate([
            'type' => ['required', Rule::in([CatalogRequest::TYPE_CHAMPIONSHIP, CatalogRequest::TYPE_CLUB])],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $name = trim($data['name']);

        // Si ya existe en el catálogo, avisar en lugar de crear la solicitud.
        $exists = $data['type'] === CatalogRequest::TYPE_CHAMPIONSHIP
            ? Championship::where('is_active', true)->where('name', 'like', $name)->exists()
            : Club::where('name', 'like', $name)->exists();

        if ($exists) {
            return $this->error('Ya existe en el catálogo con ese nombre exacto. Búscalo en el selector.', 422);
        }

        // Evitar duplicados y spam de solicitudes.
        $duplicate = CatalogRequest::where('type', $data['type'])
            ->where('name', 'like', $name)
            ->where('status', CatalogRequest::STATUS_PENDING)
            ->exists();

        if ($duplicate) {
            return $this->error('Ya tienes una solicitud pendiente con ese nombre.', 422);
        }

        if (CatalogRequest::where('status', CatalogRequest::STATUS_PENDING)->count() >= 10) {
            return $this->error('Tienes demasiadas solicitudes pendientes. Espera a que se procesen.', 422);
        }

        $req = CatalogRequest::create([
            'tenant_id' => $tenant->id,
            'type' => $data['type'],
            'name' => $name,
            'comment' => $data['comment'] ?? null,
            'status' => CatalogRequest::STATUS_PENDING,
        ]);

        return $this->success(
            ['id' => $req->id],
            'Solicitud enviada. Te avisaremos cuando esté disponible en el catálogo.',
            201
        );
    }

    /** GET referee/championships — oficiales activos + los personales del árbitro. */
    public function championships(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        return $this->success([
            'championships' => Championship::visibleTo($tenant->id)
                ->orderBy('name')
                ->get(['id', 'name', 'category', 'season', 'tenant_id'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'category' => $c->category,
                    'season' => $c->season,
                    'is_personal' => $c->tenant_id !== null,
                ]),
        ]);
    }

    /** GET referee/clubs?search= — oficiales + los personales del árbitro. */
    public function clubs(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $search = trim($request->string('search')->toString());

        return $this->success([
            'clubs' => Club::visibleTo($tenant->id)
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'name', 'city', 'tenant_id'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'city' => $c->city,
                    'is_personal' => $c->tenant_id !== null,
                ]),
        ]);
    }

    /** POST referee/championships — el árbitro crea un campeonato PARA SÍ MISMO. */
    public function storeChampionship(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'category' => ['nullable', 'string', 'max:50'],
            'season' => ['nullable', 'string', 'max:20'],
        ]);

        $name = trim($data['name']);

        // Si ya lo ve (oficial o personal propio), reutilizarlo en vez de duplicar.
        $existing = Championship::visibleTo($tenant->id)->where('name', 'like', $name)->first();
        if ($existing) {
            return $this->success(
                ['id' => $existing->id, 'name' => $existing->name, 'is_personal' => $existing->tenant_id !== null, 'reused' => true],
                'Ya existía en tu catálogo; lo seleccionamos.'
            );
        }

        $ch = Championship::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'category' => $data['category'] ?? null,
            'season' => $data['season'] ?? null,
            'is_active' => true,
        ]);

        return $this->success(
            ['id' => $ch->id, 'name' => $ch->name, 'is_personal' => true],
            'Campeonato creado para tu cuenta.',
            201
        );
    }

    /** POST referee/clubs — el árbitro crea un club PARA SÍ MISMO. */
    public function storeClub(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant->isReferee()) {
            return $this->error('El módulo de árbitros no está activo para esta cuenta.', 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $name = trim($data['name']);

        $existing = Club::visibleTo($tenant->id)->where('name', 'like', $name)->first();
        if ($existing) {
            return $this->success(
                ['id' => $existing->id, 'name' => $existing->name, 'city' => $existing->city, 'is_personal' => $existing->tenant_id !== null, 'reused' => true],
                'Ya existía en tu catálogo; lo seleccionamos.'
            );
        }

        $club = Club::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'city' => $data['city'] ?? null,
        ]);

        return $this->success(
            ['id' => $club->id, 'name' => $club->name, 'city' => $club->city, 'is_personal' => true],
            'Club creado para tu cuenta.',
            201
        );
    }
}
