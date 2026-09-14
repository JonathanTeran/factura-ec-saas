<?php

namespace Tests\Feature\Arbitros;

use App\Jobs\Arbitros\SyncFefMatchesJob;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * El lock anti-solapamiento de la sincronización FEF debe expirar. Si un worker
 * muere a mitad de corrida (p. ej. por timeout) el lock no se libera y, sin
 * expiración, cada corrida siguiente se reencolaba hasta fallar con
 * MaxAttemptsExceededException: la sync no volvía a ejecutarse nunca.
 */
class SyncFefMatchesJobOverlapTest extends TestCase
{
    public function test_a_lock_left_by_a_killed_worker_expires_and_the_next_run_proceeds(): void
    {
        $job = new SyncFefMatchesJob;
        $overlap = collect($job->middleware())
            ->first(fn ($middleware) => $middleware instanceof WithoutOverlapping);
        $this->assertNotNull($overlap, 'La sync FEF debe seguir protegida contra solapamiento.');

        // Worker muerto a mitad de corrida: tomó el lock y nunca lo liberó.
        $this->assertTrue(Cache::lock($overlap->getLockKey($job), $overlap->expiresAfter)->get());

        $ran = false;
        $run = function () use (&$ran) {
            $ran = true;
        };

        $overlap->handle($job, $run);
        $this->assertFalse($ran, 'Mientras la corrida anterior puede seguir viva, no se solapa.');

        // La corrida más larga termina a los $timeout segundos (el worker la
        // mata). Pasado ese plazo y la expiración del lock, la siguiente corre.
        $this->travel(max($job->timeout, (int) $overlap->expiresAfter) + 1)->seconds();

        $overlap->handle($job, $run);
        $this->assertTrue($ran, 'El lock huérfano debe expirar para que la sync vuelva a correr.');
    }
}
