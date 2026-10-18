<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Normalises every API JSON body to { data, meta, message } (plus `errors` for validation failures).
 */
class UnifiedJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $body = $response->getData(true);

        if (! is_array($body) || ! array_key_exists('data', $body)) {
            $body = ['data' => $body];
        }

        $response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->setData([
            'data' => $body['data'],
            'meta' => $body['meta'] ?? null,
            'message' => $body['message'] ?? null,
        ] + array_diff_key($body, array_flip(['data', 'meta', 'message', 'links'])));

        return $response;
    }
}
