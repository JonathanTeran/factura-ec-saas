<?php

namespace App\Services\Arbitros;

use App\Models\Arbitros\FefSyncRun;
use App\Models\Tenant\Tenant;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orquesta una sincronización completa con la FEF y la deja registrada en
 * fef_sync_runs: ingesta (campeonatos, clubes, partidos) → auto-matching de
 * árbitros → directorio de árbitros. Es el único punto de entrada del job
 * programado, del comando y del botón del panel.
 */
class FefSyncService
{
    public function __construct(
        private FefApiClient $api,
        private FefIngestService $ingest,
        private RefereeMatcher $matcher,
        private FefRefereeDirectory $directory,
        private NotificationService $notifications,
    ) {}

    /** ¿Hay cuentas de árbitro? Sin ellas no se llama a la FEF. */
    public function hasRefereeTenants(): bool
    {
        return Tenant::where('business_type', Tenant::BUSINESS_TYPE_REFEREE)->exists();
    }

    /**
     * Ejecuta y registra una corrida. Relanza la excepción tras registrarla
     * (así Horizon reintenta y el fallo queda visible en el panel).
     */
    public function run(string $trigger = FefSyncRun::TRIGGER_SCHEDULE, ?int $userId = null): FefSyncRun
    {
        $previousWasFailed = FefSyncRun::latest()?->isFailed() ?? false;

        $run = FefSyncRun::create([
            'trigger' => $trigger,
            'status' => FefSyncRun::STATUS_RUNNING,
            'started_at' => Carbon::now(),
            'triggered_by' => $userId,
        ]);

        $startedAt = microtime(true);
        $this->api->resetErrors();

        try {
            $stats = $this->ingest->sync();
            $stats += $this->matcher->run();
            $stats += $this->directory->refresh();

            $apiErrors = $this->api->errors();
            $status = $apiErrors === [] ? FefSyncRun::STATUS_SUCCESS : FefSyncRun::STATUS_PARTIAL;

            $run->update([
                'status' => $status,
                'finished_at' => Carbon::now(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'stats' => $stats,
                'api_errors' => $apiErrors,
            ]);

            Log::info('[arbitros] Sync FEF terminado', ['run_id' => $run->id, 'status' => $status] + $stats);
        } catch (Throwable $e) {
            $run->update([
                'status' => FefSyncRun::STATUS_FAILED,
                'finished_at' => Carbon::now(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'api_errors' => $this->api->errors(),
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            Log::error('[arbitros] Sync FEF falló', ['run_id' => $run->id, 'error' => $e->getMessage()]);

            // Avisar solo al pasar a fallido: una API caída una hora entera no
            // debe generar un correo por cada reintento/corrida.
            if (! $previousWasFailed) {
                $this->notifications->notifyAdmins('fef_sync.failed', [
                    'run_id' => $run->id,
                    'trigger' => $run->triggerLabel(),
                    'error' => $e->getMessage(),
                    'started_at' => $run->started_at->format('d/m/Y H:i'),
                ]);
            }

            throw $e;
        }

        $this->prune();

        return $run->fresh();
    }

    /** Avisa a los super admins si no hay una corrida OK dentro de la ventana. */
    public function checkHealth(): bool
    {
        if (! $this->hasRefereeTenants() || ! FefSyncRun::isStale()) {
            return true;
        }

        $last = FefSyncRun::latestOk();

        $this->notifications->notifyAdmins('fef_sync.stale', [
            'hours' => (int) config('arbitros.sync.stale_hours', 3),
            'last_ok_at' => $last?->started_at?->format('d/m/Y H:i') ?? 'nunca',
            'last_status' => FefSyncRun::latest()?->statusLabel() ?? 'sin corridas',
        ]);

        return false;
    }

    /** Borra el historial más antiguo que arbitros.sync.keep_days. */
    private function prune(): void
    {
        $days = (int) config('arbitros.sync.keep_days', 90);

        FefSyncRun::where('started_at', '<', Carbon::now()->subDays($days))->delete();
    }
}
