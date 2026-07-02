<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compresses JSON API responses with gzip when the client supports it.
 * Targets responses > 1KB — smaller payloads cost more CPU than they save.
 */
class CompressResponse
{
    private const MIN_BYTES = 1024;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || strlen($content) < self::MIN_BYTES) {
            return $response;
        }

        $compressed = gzencode($content, 6);

        if ($compressed === false || strlen($compressed) >= strlen($content)) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }

    private function shouldCompress(Request $request, Response $response): bool
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');

        if (! str_contains($acceptEncoding, 'gzip')) {
            return false;
        }

        // Skip binary responses and already-compressed content
        $contentType  = $response->headers->get('Content-Type', '');
        $alreadyEncoded = $response->headers->has('Content-Encoding');

        return ! $alreadyEncoded && (
            str_contains($contentType, 'application/json') ||
            str_contains($contentType, 'text/')
        );
    }
}
