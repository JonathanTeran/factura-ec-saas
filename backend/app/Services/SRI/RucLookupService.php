<?php

namespace App\Services\SRI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta el catastro público del SRI (SRI en Línea) para obtener los datos
 * de un contribuyente y sus establecimientos a partir del RUC.
 *
 * El servicio es público y no requiere autenticación, pero rechaza peticiones
 * sin un User-Agent de navegador.
 */
class RucLookupService
{
    /**
     * Datos normalizados del contribuyente, o null si el RUC no existe
     * o el SRI no está disponible.
     *
     * @return array{ruc: string, business_name: string, status: string, taxpayer_type: string, regime: string, obligated_accounting: bool, retention_agent: bool, special_taxpayer: bool, main_activity: ?string}|null
     */
    public function lookup(string $ruc): ?array
    {
        if (! $this->isValidRucFormat($ruc)) {
            return null;
        }

        $data = $this->fetchJson(
            config('sri.ruc_lookup.taxpayer_url'),
            ['ruc' => $ruc],
            "sri.ruc_lookup.{$ruc}"
        );

        $taxpayer = $data[0] ?? null;

        if (! is_array($taxpayer) || empty($taxpayer['numeroRuc'])) {
            return null;
        }

        return [
            'ruc' => $taxpayer['numeroRuc'],
            'business_name' => $taxpayer['razonSocial'] ?? '',
            'status' => $taxpayer['estadoContribuyenteRuc'] ?? '',
            'taxpayer_type' => $this->mapTaxpayerType($taxpayer['tipoContribuyente'] ?? ''),
            'regime' => $this->mapRegime($taxpayer['regimen'] ?? '', $taxpayer['categoria'] ?? null),
            'obligated_accounting' => ($taxpayer['obligadoLlevarContabilidad'] ?? 'NO') === 'SI',
            'retention_agent' => ($taxpayer['agenteRetencion'] ?? 'NO') === 'SI',
            'special_taxpayer' => ($taxpayer['contribuyenteEspecial'] ?? 'NO') === 'SI',
            'main_activity' => $taxpayer['actividadEconomicaPrincipal'] ?? null,
        ];
    }

    /**
     * Busca por cédula (10 dígitos) o RUC (13 dígitos). Para cédulas se
     * consulta el catastro con el RUC de persona natural (cédula + "001");
     * si la persona no tiene RUC, el catastro devuelve vacío y retornamos null.
     */
    public function lookupIdentification(string $identification): ?array
    {
        if (preg_match('/^[0-9]{10}$/', $identification) === 1) {
            return $this->lookup($identification . '001');
        }

        return $this->lookup($identification);
    }

    /**
     * Establecimientos registrados en el SRI para el RUC dado.
     *
     * @return array<int, array{code: string, trade_name: ?string, address: ?string, is_main: bool, is_open: bool}>
     */
    public function establishments(string $ruc): array
    {
        if (! $this->isValidRucFormat($ruc)) {
            return [];
        }

        $data = $this->fetchJson(
            config('sri.ruc_lookup.establishments_url'),
            ['numeroRuc' => $ruc],
            "sri.ruc_establishments.{$ruc}"
        );

        if (! is_array($data)) {
            return [];
        }

        return collect($data)
            ->filter(fn ($item) => is_array($item) && ! empty($item['numeroEstablecimiento']))
            ->map(fn (array $item) => [
                'code' => $item['numeroEstablecimiento'],
                'trade_name' => $item['nombreFantasiaComercial'] ?? null,
                'address' => $item['direccionCompleta'] ?? null,
                'is_main' => ($item['matriz'] ?? 'NO') === 'SI',
                'is_open' => ($item['estado'] ?? '') === 'ABIERTO',
            ])
            ->values()
            ->all();
    }

    private function isValidRucFormat(string $ruc): bool
    {
        return preg_match('/^[0-9]{13}$/', $ruc) === 1;
    }

    private function fetchJson(string $url, array $query, string $cacheKey): ?array
    {
        $ttl = (int) config('sri.ruc_lookup.cache_ttl_minutes', 360);

        try {
            return Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($url, $query) {
                $response = Http::withHeaders([
                    // El SRI deja colgadas las peticiones sin User-Agent de navegador
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) FacturaEC',
                    'Accept' => 'application/json',
                ])
                    ->timeout((int) config('sri.ruc_lookup.timeout_seconds', 10))
                    ->get($url, $query);

                if (! $response->successful()) {
                    return null;
                }

                return $response->json();
            });
        } catch (\Throwable $e) {
            Log::warning('SRI RUC lookup failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function mapTaxpayerType(string $type): string
    {
        return str_contains(mb_strtoupper($type), 'NATURAL') ? 'natural' : 'juridical';
    }

    private function mapRegime(string $regime, ?string $category): string
    {
        if (mb_strtoupper($regime) !== 'RIMPE') {
            return 'general';
        }

        return str_contains(mb_strtoupper((string) $category), 'POPULAR')
            ? 'rimpe_popular'
            : 'rimpe_emprendedor';
    }
}
