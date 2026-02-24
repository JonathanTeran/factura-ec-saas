<?php

namespace App\Jobs;

use App\Models\Tenant\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 30;
    public array $backoff = [10, 30, 120, 300, 900];

    public function __construct(
        public WebhookEndpoint $endpoint,
        public string $event,
        public array $payload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        if (!$this->endpoint->is_active || $this->endpoint->isDisabledDueToFailures()) {
            Log::info("Webhook endpoint {$this->endpoint->id} is inactive or disabled, skipping");
            return;
        }

        $timestamp = now()->timestamp;
        $body = json_encode([
            'event' => $this->event,
            'timestamp' => $timestamp,
            'data' => $this->payload,
        ]);

        $signature = $this->generateSignature($body, $timestamp);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $this->event,
                    'X-Webhook-Timestamp' => (string) $timestamp,
                    'X-Webhook-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($this->endpoint->url);

            if ($response->successful()) {
                $this->endpoint->recordSuccess();
                Log::info("Webhook delivered to endpoint {$this->endpoint->id} for event {$this->event}");
            } else {
                $this->handleFailure("HTTP {$response->status()}: {$response->body()}");
            }

        } catch (\Exception $e) {
            $this->handleFailure($e->getMessage());
            throw $e;
        }
    }

    protected function generateSignature(string $body, int $timestamp): string
    {
        $payload = "{$timestamp}.{$body}";

        return hash_hmac('sha256', $payload, $this->endpoint->secret);
    }

    protected function handleFailure(string $reason): void
    {
        $this->endpoint->recordFailure();

        Log::warning("Webhook delivery failed for endpoint {$this->endpoint->id}: {$reason}", [
            'event' => $this->event,
            'attempt' => $this->attempts(),
            'failure_count' => $this->endpoint->failure_count,
        ]);

        if ($this->endpoint->isDisabledDueToFailures()) {
            Log::error("Webhook endpoint {$this->endpoint->id} disabled after 10 consecutive failures");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendWebhookJob permanently failed for endpoint {$this->endpoint->id}: {$exception->getMessage()}");
        $this->endpoint->recordFailure();
    }
}
