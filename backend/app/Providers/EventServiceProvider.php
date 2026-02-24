<?php

namespace App\Providers;

use App\Events\DocumentAuthorized;
use App\Events\DocumentCreated;
use App\Events\DocumentRejected;
use App\Events\PurchaseRegistered;
use App\Events\TenantCreated;
use App\Listeners\CheckPlanLimitsAfterDocument;
use App\Listeners\GenerateAccountingEntry;
use App\Listeners\GenerateAccountingEntryForPurchase;
use App\Listeners\LogDocumentActivity;
use App\Listeners\SendDocumentAuthorizedNotification;
use App\Listeners\SendDocumentRejectedNotification;
use App\Listeners\SendWelcomeNotification;
use App\Listeners\UpdateDocumentCount;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        DocumentCreated::class => [
            UpdateDocumentCount::class,
            CheckPlanLimitsAfterDocument::class,
        ],

        DocumentAuthorized::class => [
            SendDocumentAuthorizedNotification::class,
            GenerateAccountingEntry::class,
        ],

        PurchaseRegistered::class => [
            GenerateAccountingEntryForPurchase::class,
        ],

        DocumentRejected::class => [
            SendDocumentRejectedNotification::class,
        ],

        TenantCreated::class => [
            SendWelcomeNotification::class,
        ],
    ];

    /**
     * The subscribers to register.
     *
     * @var array
     */
    protected $subscribe = [
        LogDocumentActivity::class,
        \App\Listeners\DispatchWebhookListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
