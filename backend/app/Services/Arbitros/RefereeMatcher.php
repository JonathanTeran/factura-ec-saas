<?php

namespace App\Services\Arbitros;

use App\Models\Arbitros\FootballMatch;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\Tenant\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Auto-matching: cruza los oficiales publicados en los partidos del catálogo
 * contra el nombre configurado de cada tenant árbitro y crea propuestas
 * `officiated_matches` (pending) con el ROL autodetectado según el campo donde
 * apareció. El árbitro solo confirma. Ver docs/arbitros-vertical-spec.md §6.
 *
 * Los nombres de la FEF vienen como "APELLIDO APELLIDO NOMBRE NOMBRE" en
 * mayúsculas; el árbitro puede escribir el suyo en cualquier orden, por eso se
 * comparan como conjuntos de tokens normalizados (sin acentos, sin orden).
 */
class RefereeMatcher
{
    /** Campo del feed → rol del vertical. */
    private const ROLE_MAP = [
        'center' => OfficiatedMatch::ROLE_ARBITRO,
        'assistant_1' => OfficiatedMatch::ROLE_ASISTENTE_1,
        'assistant_2' => OfficiatedMatch::ROLE_ASISTENTE_2,
        'fourth' => OfficiatedMatch::ROLE_CUARTO,
    ];

    /**
     * Corre el matching para todos los tenants árbitro (o uno en particular).
     *
     * @return array{tenants: int, proposals: int}
     */
    public function run(?int $tenantId = null, ?int $sinceDays = null, bool $dryRun = false): array
    {
        $sinceDays ??= (int) config('arbitros.matching.since_days', 60);
        $since = Carbon::now()->subDays($sinceDays)->startOfDay();

        $tenants = Tenant::query()
            ->where('business_type', Tenant::BUSINESS_TYPE_REFEREE)
            ->when($tenantId, fn ($q) => $q->where('id', $tenantId))
            ->get()
            ->filter(fn (Tenant $t) => filled(data_get($t->settings, 'referee_name')));

        $stats = ['tenants' => $tenants->count(), 'proposals' => 0];

        if ($tenants->isEmpty()) {
            return $stats;
        }

        // Índice: nombre normalizado → [{match_id, role, match}, …] una sola vez.
        $index = $this->buildOfficialsIndex($since);

        foreach ($tenants as $tenant) {
            $key = $this->normalize((string) data_get($tenant->settings, 'referee_name'));

            foreach ($index[$key] ?? [] as $entry) {
                /** @var FootballMatch $match */
                $match = $entry['match'];
                $role = $entry['role'];

                $exists = OfficiatedMatch::withoutTenantScope()
                    ->where('tenant_id', $tenant->id)
                    ->where('football_match_id', $match->id)
                    ->where('role', $role)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $stats['proposals']++;

                if ($dryRun) {
                    continue;
                }

                OfficiatedMatch::withoutTenantScope()->create([
                    'tenant_id' => $tenant->id,
                    'football_match_id' => $match->id,
                    'championship_id' => $match->championship_id,
                    'home_club_id' => $match->home_club_id,
                    'away_club_id' => $match->away_club_id,
                    'match_date' => $match->match_date,
                    'role' => $role,
                    'fee' => (float) data_get($tenant->settings, 'referee_default_fee', 0),
                    'status' => OfficiatedMatch::STATUS_PENDING,
                    'source' => 'scraper',
                ]);
            }
        }

        return $stats;
    }

    /**
     * Normaliza un nombre a una clave comparable: sin acentos, mayúsculas,
     * solo letras, tokens ordenados. "Kevin Póveda" y "POVEDA KEVIN" → igual.
     */
    public function normalize(string $name): string
    {
        $ascii = Str::ascii(mb_strtoupper(trim($name)));
        $clean = preg_replace('/[^A-Z ]+/', ' ', $ascii) ?? '';
        $tokens = preg_split('/\s+/', trim($clean), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($tokens);

        return implode(' ', $tokens);
    }

    /** @return array<string, array<int, array{role: string, match: FootballMatch}>> */
    private function buildOfficialsIndex(Carbon $since): array
    {
        $index = [];

        FootballMatch::query()
            ->whereNotNull('officials')
            ->whereDate('match_date', '>=', $since)
            ->orderBy('id')
            ->chunk(500, function ($matches) use (&$index) {
                foreach ($matches as $match) {
                    foreach (self::ROLE_MAP as $field => $role) {
                        $name = (string) ($match->officials[$field] ?? '');

                        if ($name === '') {
                            continue;
                        }

                        $index[$this->normalize($name)][] = [
                            'role' => $role,
                            'match' => $match,
                        ];
                    }
                }
            });

        return $index;
    }
}
