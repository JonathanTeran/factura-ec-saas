<?php

namespace Tests\Feature;

use App\Notifications\SignatureExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class SignatureExpiryReminderTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();
    }

    public function test_sends_reminder_when_signature_expires_in_15_days(): void
    {
        $this->company->update(['signature_expires_at' => now()->addDays(15)]);

        $this->artisan('sri:send-signature-expiry-reminders')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->tenant->owner,
            SignatureExpiringNotification::class,
            fn ($notification) => $notification->daysUntilExpiry === 15
                && $notification->company->id === $this->company->id
        );
    }

    public function test_does_not_send_outside_reminder_windows(): void
    {
        $this->company->update(['signature_expires_at' => now()->addDays(20)]);

        $this->artisan('sri:send-signature-expiry-reminders')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_send_for_companies_without_signature(): void
    {
        $this->company->update(['signature_expires_at' => null]);

        $this->artisan('sri:send-signature-expiry-reminders')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}
