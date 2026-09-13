<?php

namespace App\Listeners;

use App\Support\HtmlToText;
use Illuminate\Mail\Events\MessageSending;

/**
 * Todo correo HTML sale también con su parte text/plain (multipart/
 * alternative). Los correos solo-HTML puntúan peor en los filtros antispam
 * de Gmail/Outlook; las notificaciones Markdown ya la traen, pero los
 * mailables con vista Blade (bienvenida, comprobantes, proformas) no.
 */
class AddPlainTextPart
{
    public function handle(MessageSending $event): void
    {
        $message = $event->message;

        if ($message->getTextBody() !== null) {
            return;
        }

        $html = $message->getHtmlBody();

        if (! is_string($html) || trim($html) === '') {
            return;
        }

        $text = HtmlToText::convert($html);

        if ($text !== '') {
            $message->text($text);
        }
    }
}
