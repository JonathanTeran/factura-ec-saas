<?php

namespace Tests\Feature;

use App\Notifications\NewUserWelcomeNotification;
use App\Notifications\PlanLimitReachedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

/**
 * Los correos salen con la marca Facturón (logo, tema navy) y terminan con el
 * bloque promocional de Facturón + AmePhia, con enlaces al panel Next.js.
 */
class MailBrandingTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        config(['app.url' => 'https://facturon.ec']);
    }

    public function test_markdown_notifications_use_the_brand_theme_and_promo_footer(): void
    {
        $html = (new PlanLimitReachedNotification($this->tenant, 'documentos', 50, 50))
            ->toMail($this->user)
            ->render();

        $this->assertStringContainsString('https://facturon.ec/marketing/email-logo.png', $html);
        $this->assertStringContainsString('amephia.com', $html);
        $this->assertStringContainsString('AmePhia Systems', $html);
        $this->assertStringContainsString('facturon.ec', $html);
        $this->assertStringContainsString('/settings/subscription', $html);
        $this->assertStringNotContainsString('/panel', $html);
        $this->assertStringContainsString('#0b1220', $html);
        $this->assertStringContainsString('Saludos,', $html);
    }

    public function test_custom_view_emails_use_the_brand_layout_and_promo_footer(): void
    {
        $html = (new NewUserWelcomeNotification)
            ->toMail($this->user)
            ->render();

        $this->assertStringContainsString('marketing/email-logo.png', $html);
        $this->assertStringContainsString('¡Bienvenido a Facturón!', $html);
        $this->assertStringContainsString('/onboarding', $html);
        $this->assertStringContainsString('amephia.com', $html);
        $this->assertStringNotContainsString('/panel', $html);
        $this->assertStringNotContainsString('Facturacion Electronica', $html);
    }
}
