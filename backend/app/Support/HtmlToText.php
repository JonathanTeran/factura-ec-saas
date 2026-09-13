<?php

namespace App\Support;

/**
 * Convierte el HTML de un correo en una versión de texto plano legible
 * (párrafos, saltos, enlaces como "texto (url)"). Sirve para adjuntar la
 * parte text/plain que los filtros antispam esperan en todo correo HTML.
 */
final class HtmlToText
{
    public static function convert(string $html): string
    {
        $text = preg_replace('#<(script|style|head)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;

        // Enlaces: "texto (url)". Se omiten los que envuelven solo imágenes.
        $text = preg_replace_callback(
            '#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is',
            function (array $m): string {
                $label = trim(strip_tags($m[2]));
                $url = trim($m[1]);
                if ($label === '' || str_starts_with($url, 'mailto:') && $label === substr($url, 7)) {
                    return $label !== '' ? $label : '';
                }

                return $label === $url ? $url : "{$label} ({$url})";
            },
            $text
        ) ?? $text;

        $text = preg_replace('#<br\s*/?>#i', "\n", $text) ?? $text;
        $text = preg_replace('#</(p|div|tr|li|h[1-6]|table|blockquote)>#i', "\n", $text) ?? $text;
        $text = preg_replace('#</t[dh]>#i', ' ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normaliza espacios: cada línea sin espacios sobrantes, máximo una línea en blanco seguida.
        $lines = array_map(fn (string $line) => trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $line) ?? $line), explode("\n", $text));
        $text = preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)) ?? $text;

        return trim($text);
    }
}
