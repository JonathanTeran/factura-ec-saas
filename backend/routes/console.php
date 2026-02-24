<?php

use App\Jobs\CheckCertificateExpiryJob;
use App\Jobs\CheckTrialEndingJob;
use App\Jobs\ProcessRecurringInvoicesJob;
use App\Jobs\ResetMonthlyDocumentCountJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
*/

// Check for trials ending - daily at 8 AM
Schedule::job(new CheckTrialEndingJob())
    ->dailyAt('08:00')
    ->name('check-trial-ending')
    ->withoutOverlapping()
    ->onOneServer();

// Check for certificate expiry - daily at 9 AM
Schedule::job(new CheckCertificateExpiryJob())
    ->dailyAt('09:00')
    ->name('check-certificate-expiry')
    ->withoutOverlapping()
    ->onOneServer();

// Reset monthly document counts - monthly on the 1st at midnight
Schedule::job(new ResetMonthlyDocumentCountJob())
    ->monthlyOn(1, '00:00')
    ->name('reset-monthly-document-count')
    ->withoutOverlapping()
    ->onOneServer();

// Horizon snapshot - every 5 minutes
Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->onOneServer();

// Clean up old telescope entries - daily
Schedule::command('telescope:prune --hours=48')
    ->daily()
    ->onOneServer();

// Clear expired password reset tokens - daily
Schedule::command('auth:clear-resets')
    ->daily()
    ->onOneServer();

// Clean up old notifications - weekly
Schedule::command('model:prune', [
    '--model' => [\Illuminate\Notifications\DatabaseNotification::class],
])
    ->weekly()
    ->onOneServer();

// Process recurring invoices - daily at 6 AM
Schedule::job(new ProcessRecurringInvoicesJob())
    ->dailyAt('06:00')
    ->name('process-recurring-invoices')
    ->withoutOverlapping()
    ->onOneServer();

// Clean expired portal tokens and sessions - daily at 3 AM
Schedule::command('portal:cleanup')
    ->dailyAt('03:00')
    ->name('portal-cleanup')
    ->withoutOverlapping()
    ->onOneServer();

// Backup - daily at 2 AM
Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->name('backup-run')
    ->withoutOverlapping()
    ->onOneServer();

// Backup cleanup - daily at 3:30 AM
Schedule::command('backup:clean')
    ->dailyAt('03:30')
    ->name('backup-clean')
    ->withoutOverlapping()
    ->onOneServer();

// Backup health check - daily at 4 AM
Schedule::command('backup:monitor')
    ->dailyAt('04:00')
    ->name('backup-monitor')
    ->onOneServer();

// Send trial ending reminders - daily at 8 AM
Schedule::command('billing:send-trial-reminders')
    ->dailyAt('08:00')
    ->name('billing-send-trial-reminders')
    ->withoutOverlapping()
    ->onOneServer();

// Check expired subscriptions - daily at midnight
Schedule::command('billing:check-expired')
    ->dailyAt('00:00')
    ->name('billing-check-expired')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Custom Artisan Commands
|--------------------------------------------------------------------------
*/

Artisan::command('sri:reprocess-pending', function () {
    $this->info('Reprocessing pending documents...');

    $documents = \App\Models\SRI\ElectronicDocument::query()
        ->whereIn('status', ['sent', 'processing'])
        ->where('created_at', '>=', now()->subDays(7))
        ->get();

    foreach ($documents as $document) {
        \App\Jobs\CheckDocumentAuthorizationJob::dispatch($document);
        $this->line("Dispatched check for document {$document->id}");
    }

    $this->info("Dispatched {$documents->count()} documents for reprocessing");
})->purpose('Reprocess documents stuck in pending/sent status');

Artisan::command('tenant:stats {tenant?}', function (?int $tenant = null) {
    $query = \App\Models\Tenant\Tenant::query();

    if ($tenant) {
        $query->where('id', $tenant);
    }

    $tenants = $query->withCount(['users', 'companies', 'documents'])->get();

    $this->table(
        ['ID', 'Name', 'Status', 'Plan', 'Users', 'Companies', 'Documents', 'Docs This Month', 'Max Docs'],
        $tenants->map(fn($t) => [
            $t->id,
            $t->name,
            $t->status->value,
            $t->plan?->name ?? 'N/A',
            $t->users_count,
            $t->companies_count,
            $t->documents_count,
            $t->documents_this_month,
            $t->max_documents_per_month,
        ])
    );
})->purpose('Display tenant statistics');
