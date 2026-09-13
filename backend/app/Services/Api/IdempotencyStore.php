<?php

namespace App\Services\Api;

use App\Models\Tenant\Tenant;
use App\Support\ApiError;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotencia para operaciones de escritura de la API de integración.
 *
 * Con la cabecera `Idempotency-Key`, la primera petición se ejecuta y su
 * respuesta se guarda 24 h por tenant; una repetición con el mismo cuerpo
 * devuelve la misma respuesta (cabecera `Idempotent-Replayed: true`); el
 * mismo key con otro cuerpo es un 409. Una petición concurrente con el
 * mismo key espera el candado o recibe 409 `idempotency_in_progress`.
 */
class IdempotencyStore
{
    public const TTL_SECONDS = 86400;

    public const MAX_KEY_LENGTH = 128;

    public function run(Tenant $tenant, ?string $key, array $payload, Closure $handler): Response
    {
        $key = is_string($key) ? trim($key) : '';

        if ($key === '') {
            return $handler();
        }

        if (mb_strlen($key) > self::MAX_KEY_LENGTH) {
            return ApiError::json(
                'validation_error',
                'La cabecera Idempotency-Key no debe superar '.self::MAX_KEY_LENGTH.' caracteres.',
                422,
                ['errors' => ['Idempotency-Key' => ['Máximo '.self::MAX_KEY_LENGTH.' caracteres.']]]
            );
        }

        $hash = $this->hashPayload($payload);
        $cacheKey = $this->cacheKey($tenant, $key);

        $replay = $this->replayIfStored($cacheKey, $hash);
        if ($replay) {
            return $replay;
        }

        $lock = Cache::lock($cacheKey.':lock', 15);

        if (! $lock->get()) {
            return ApiError::json(
                'idempotency_in_progress',
                'Ya hay una petición en curso con este Idempotency-Key. Reintenta en unos segundos.',
                409
            );
        }

        try {
            // Otra petición pudo completar mientras esperábamos el candado.
            $replay = $this->replayIfStored($cacheKey, $hash);
            if ($replay) {
                return $replay;
            }

            $response = $handler();

            if ($response instanceof JsonResponse && $this->isStorable($response)) {
                Cache::put($cacheKey, [
                    'hash' => $hash,
                    'status' => $response->getStatusCode(),
                    'body' => $response->getData(true),
                ], self::TTL_SECONDS);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    protected function replayIfStored(string $cacheKey, string $hash): ?JsonResponse
    {
        $stored = Cache::get($cacheKey);

        if (! is_array($stored)) {
            return null;
        }

        if (($stored['hash'] ?? null) !== $hash) {
            return ApiError::json(
                'idempotency_key_reused',
                'Este Idempotency-Key ya se usó con un cuerpo distinto. Usa una clave nueva por cada operación.',
                409
            );
        }

        return response()->json($stored['body'], (int) $stored['status'])
            ->header('Idempotent-Replayed', 'true');
    }

    protected function isStorable(JsonResponse $response): bool
    {
        $status = $response->getStatusCode();

        return $status < 500 && $status !== 429;
    }

    protected function hashPayload(array $payload): string
    {
        ksort($payload);

        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function cacheKey(Tenant $tenant, string $key): string
    {
        return 'idem:'.$tenant->id.':'.hash('sha256', $key);
    }
}
