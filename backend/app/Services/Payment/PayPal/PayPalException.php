<?php

namespace App\Services\Payment\PayPal;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Error de la API de PayPal con el código de "issue" (INSTRUMENT_DECLINED,
 * ORDER_ALREADY_CAPTURED…) y el debug_id que pide soporte de PayPal.
 */
class PayPalException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $issue = null,
        public readonly int $status = 0,
        public readonly ?string $debugId = null,
    ) {
        parent::__construct($message);
    }

    public static function notConfigured(): self
    {
        return new self('PayPal no está configurado: faltan el Client ID o el Secret.', 'NOT_CONFIGURED');
    }

    public static function fromResponse(Response $response, ?string $fallbackMessage = null): self
    {
        $json = $response->json();
        $json = is_array($json) ? $json : [];

        $issue = data_get($json, 'details.0.issue') ?? data_get($json, 'name') ?? data_get($json, 'error');
        $description = data_get($json, 'details.0.description')
            ?? data_get($json, 'message')
            ?? data_get($json, 'error_description');

        $message = $fallbackMessage ?? ($description ? "PayPal: {$description}" : "PayPal respondió HTTP {$response->status()}.");

        return new self(
            $message,
            is_string($issue) ? $issue : null,
            $response->status(),
            is_string(data_get($json, 'debug_id')) ? data_get($json, 'debug_id') : null,
        );
    }

    public function hasIssue(string ...$issues): bool
    {
        return $this->issue !== null && in_array($this->issue, $issues, true);
    }

    /** Fallo de red o de PayPal (5xx): conviene reintentar más tarde. */
    public function isTransient(): bool
    {
        return $this->status === 0 || $this->status >= 500 || $this->status === 429;
    }
}
