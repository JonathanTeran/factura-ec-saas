<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Respuestas de error de la API de integración: siempre el mismo envoltorio
 * (`success`, `error` en snake_case estable para el integrador, `message`).
 */
final class ApiError
{
    public static function json(string $code, string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'error' => $code,
            'message' => $message,
        ], $extra), $status);
    }
}
