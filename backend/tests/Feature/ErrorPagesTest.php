<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Páginas de error con la marca (nunca la pantalla por defecto de Laravel). */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_404_is_branded(): void
    {
        $response = $this->get('/esta-ruta-no-existe');

        $response->assertNotFound()
            ->assertSee('No encontramos esta página')
            ->assertSee('Facturón')
            ->assertSee('Ir al inicio')
            ->assertDontSee('NOT FOUND', false);
    }

    public function test_invalid_signed_link_explains_and_offers_a_new_link(): void
    {
        $response = $this->get('/admin/password-reset/reset?email=admin%40example.com&token=abc&signature=deadbeef');

        $response->assertForbidden()
            ->assertSee('Este enlace ya no es válido')
            ->assertSee('/admin/password-reset/request', false)
            ->assertDontSee('Invalid signature', false);
    }

    public function test_api_paths_always_get_json_errors(): void
    {
        // Sin cabecera Accept (curl, navegador): igual JSON, nunca HTML.
        $this->get('/api/v1/esta-ruta-no-existe')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('error', 'not_found');

        $this->get('/api/v1/ext/me')
            ->assertStatus(401)
            ->assertJsonPath('error', 'missing_api_key');
    }

    public function test_other_error_views_render_with_the_brand(): void
    {
        foreach ([401, 419, 429, 500, 503] as $status) {
            $html = view("errors.{$status}", ['exception' => new HttpException($status)])->render();

            $this->assertStringContainsString('Facturón', $html, "vista {$status}");
            $this->assertStringContainsString('#0B1220', $html, "vista {$status}");
            $this->assertStringContainsString('info@amephia.com', $html, "vista {$status}");
        }

        $this->assertStringContainsString('Estamos actualizando Facturón', view('errors.503', ['exception' => new HttpException(503)])->render());
        $this->assertStringContainsString('402', view('errors.4xx', ['exception' => new HttpException(402)])->render());
        $this->assertStringContainsString('502', view('errors.5xx', ['exception' => new HttpException(502)])->render());
    }
}
