<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Idempotency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
      
        // Check if the request contains an idempotency key
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey) {
            // Check if the request has been processed previously
            if (Cache::has($idempotencyKey)) {
                // Return the cached response
                return response()->json([
                    'message' => 'Idempotent request: Response returned from cache',
                    'data' => Cache::get($idempotencyKey),
                ]);
            } else {
                // Proceed with the request and cache the response
                $response = $next($request);

                // Cache the response using the idempotency key
                Cache::put($idempotencyKey, $response->getContent(), now()->addMinutes(60)); // Adjust expiry time as needed
            }
        }

        return $next($request);
    }
}

