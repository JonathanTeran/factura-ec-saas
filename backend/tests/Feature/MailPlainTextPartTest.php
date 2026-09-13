<?php

namespace Tests\Feature;

use App\Support\HtmlToText;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Entregabilidad: los correos HTML salen con parte text/plain. */
class MailPlainTextPartTest extends TestCase
{
    public function test_html_only_mail_gets_a_plain_text_alternative(): void
    {
        Mail::html('<h1>Hola Ana</h1><p>Tu factura está lista.<br>Total: <strong>$115.00</strong></p><p><a href="https://facturon.ec/documents/1">Ver documento</a></p>', function ($message) {
            $message->to('ana@example.com')->subject('Prueba');
        });

        $sent = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);

        $email = $sent[0]->getOriginalMessage();
        $this->assertNotNull($email->getHtmlBody());
        $text = (string) $email->getTextBody();
        $this->assertStringContainsString('Hola Ana', $text);
        $this->assertStringContainsString('Total: $115.00', $text);
        $this->assertStringContainsString('Ver documento (https://facturon.ec/documents/1)', $text);
        $this->assertStringNotContainsString('<', $text);
    }

    public function test_converter_strips_styles_and_keeps_structure(): void
    {
        $text = HtmlToText::convert('<html><head><style>.x{color:red}</style></head><body><table><tr><td>Número</td><td>001-001-000000001</td></tr></table><p>Fin&nbsp;&amp; listo</p></body></html>');

        $this->assertSame("Número 001-001-000000001\n\nFin & listo", $text);
    }
}
