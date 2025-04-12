<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return $next($request); // Skip if no key provided
        }

        $cacheKey = "idempotency_{$idempotencyKey}";

        if (Cache::has($cacheKey)) {
            return response()->json(
                Cache::get($cacheKey),
                Response::HTTP_OK
            );
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            Cache::put($cacheKey, $response->getContent(), now()->addMinutes(5));
        }

        return $response;
    }
}
