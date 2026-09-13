<?php

namespace App\Services\Arbitros;

use App\Models\Arbitros\FefReferee;
use App\Models\Arbitros\FootballMatch;
use App\Models\Tenant\Tenant;
use Illuminate\Support\Carbon;

/**
 * Directorio de árbitros a partir de los oficiales publicados en los partidos
 * FEF. La FEF no expone un padrón de árbitros: esta tabla derivada es la mejor
 * fuente para que el super admin vea quiénes arbitran, cuánto y si ya tienen
 * cuenta en Facturón.
 */
class FefRefereeDirectory
{
    public function __construct(private RefereeMatcher $matcher) {}

    /**
     * Recalcula el directorio completo (idempotente).
     *
     * @return array{referees: int, referees_linked: int}
     */
    public function refresh(): array
    {
        $agg = [];

        FootballMatch::query()
            ->whereNotNull('officials')
            ->select(['id', 'match_date', 'officials'])
            ->orderBy('id')
            ->chunk(500, function ($matches) use (&$agg) {
                foreach ($matches as $match) {
                    $date = $match->match_date instanceof Carbon
                        ? $match->match_date->toDateString()
                        : (string) $match->match_date;

                    foreach ((array) $match->officials as $role => $name) {
                        $name = trim((string) $name);
                        if ($name === '' || ! array_key_exists($role, FefReferee::ROLE_LABELS)) {
                            continue;
                        }

                        $key = $this->matcher->normalize($name);
                        if ($key === '') {
                            continue;
                        }

                        $entry = &$agg[$key];
                        $entry ??= ['name' => $name, 'count' => 0, 'roles' => [], 'first' => $date, 'last' => $date];
                        $entry['count']++;
                        $entry['roles'][$role] = ($entry['roles'][$role] ?? 0) + 1;
                        if ($date < $entry['first']) {
                            $entry['first'] = $date;
                        }
                        if ($date > $entry['last']) {
                            $entry['last'] = $date;
                        }
                        unset($entry);
                    }
                }
            });

        $tenantsByKey = $this->refereeTenantsByNormalizedName();
        $linked = 0;
        $seen = [];

        foreach ($agg as $key => $entry) {
            $tenantId = $tenantsByKey[$key] ?? null;
            if ($tenantId !== null) {
                $linked++;
            }

            FefReferee::updateOrCreate(
                ['normalized_name' => $key],
                [
                    'name' => $entry['name'],
                    'matches_count' => $entry['count'],
                    'roles' => $entry['roles'],
                    'first_seen_at' => $entry['first'],
                    'last_seen_at' => $entry['last'],
                    'tenant_id' => $tenantId,
                ]
            );
            $seen[] = $key;
        }

        // Nombres que ya no aparecen en ningún partido (p. ej. partidos borrados).
        if ($seen !== []) {
            FefReferee::whereNotIn('normalized_name', $seen)->delete();
        } else {
            FefReferee::query()->delete();
        }

        return ['referees' => count($agg), 'referees_linked' => $linked];
    }

    /** @return array<string, int> nombre normalizado → tenant_id */
    private function refereeTenantsByNormalizedName(): array
    {
        $map = [];

        Tenant::query()
            ->where('business_type', Tenant::BUSINESS_TYPE_REFEREE)
            ->get(['id', 'settings'])
            ->each(function (Tenant $tenant) use (&$map) {
                $name = (string) data_get($tenant->settings, 'referee_name', '');
                if ($name === '') {
                    return;
                }
                $key = $this->matcher->normalize($name);
                if ($key !== '' && ! isset($map[$key])) {
                    $map[$key] = $tenant->id;
                }
            });

        return $map;
    }
}
