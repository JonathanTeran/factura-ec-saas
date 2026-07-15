<?php

namespace App\Services\Arbitros;

use Illuminate\Support\Facades\Http;

/**
 * Cliente de la API pública de la FEF (https://apiweb.fef.ec/api/public).
 * Solo consume endpoints públicos documentados en su Swagger.
 * Ver docs/arbitros-vertical-spec.md §6 y §13.
 */
class FefApiClient
{
    /**
     * Lista de competiciones con partidos recientes.
     *
     * @return array<int, array{hierarchy_id: string, competition_name: string, path: string}>
     */
    public function competitions(): array
    {
        $data = $this->get('/competitions/matches/recent/competitions');

        return is_array($data) ? $data : [];
    }

    /**
     * Partidos jugados recientes. Con $hierarchyId limita a una competición.
     * Cada partido trae equipos, fecha, torneo y oficiales (terna + cuarto).
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentMatches(?string $hierarchyId = null): array
    {
        $query = $hierarchyId ? ['hierarchy_id' => $hierarchyId] : [];
        $data = $this->get('/competitions/matches/recent', $query);

        // La respuesta agrupa por competición/etapa según el endpoint; aplanamos
        // a una lista de partidos (todo dict con 'match_id' es un partido).
        $matches = [];
        $this->collectMatches($data, $matches);

        return $matches;
    }

    /**
     * GET genérico contra la API pública. Devuelve `data` o null si falla.
     * Nunca lanza: un endpoint caído no debe tumbar la sincronización completa
     * (la ingesta es best-effort e idempotente; la próxima corrida recupera).
     */
    private function get(string $path, array $query = []): mixed
    {
        $base = rtrim((string) config('arbitros.api.base_url'), '/');

        try {
            $response = Http::timeout((int) config('arbitros.api.timeout', 30))
                ->connectTimeout(15)
                ->retry(2, 1000, throw: false)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (compatible; FacturaEC/1.0)',
                ])
                ->get($base . $path, $query);
        } catch (\Throwable) {
            return null; // timeout de conexión u otro fallo de red
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json('data');
    }

    /** Recorre la estructura anidada y acumula todo objeto que sea un partido. */
    private function collectMatches(mixed $node, array &$out): void
    {
        if (! is_array($node)) {
            return;
        }

        if (array_key_exists('match_id', $node) && array_key_exists('match_date', $node)) {
            $out[] = $node;

            return;
        }

        foreach ($node as $child) {
            $this->collectMatches($child, $out);
        }
    }
}
