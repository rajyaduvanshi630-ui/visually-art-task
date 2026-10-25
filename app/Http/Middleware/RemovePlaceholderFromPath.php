<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemovePlaceholderFromPath
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        // Do not parse/rebuild query parameters: preserve their original bytes.
        $uri = (string) $request->server->get('REQUEST_URI', '/');
        $queryPosition = strpos($uri, '?');
        $path = $queryPosition === false ? $uri : substr($uri, 0, $queryPosition);
        $query = $queryPosition === false ? '' : substr($uri, $queryPosition);

        // Match literal or URL-encoded braces, including mixed forms.
        // Placeholder names are identifiers, e.g. {ignore} or {tracking_id}.
        $cleanPath = preg_replace(
            '/(?:\{|%7b)[a-z_][a-z0-9_-]*(?:\}|%7d)/i',
            '',
            $path
        );

        if ($cleanPath === null || $cleanPath === $path) {
            return $next($request);
        }

        // Keep Location local even if removing a token exposes //example.com.
        $cleanPath = '/'.ltrim($cleanPath, '/');

        // A plain response avoids URL-generator/query normalization.
        return new Response('', 301, ['Location' => $cleanPath.$query]);
    }
}
