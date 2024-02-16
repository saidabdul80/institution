<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Response;
use \Illuminate\Cache\RateLimiter;

class CustomThrottleMiddleware
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next, $key = 'default', $maxAttempts = 2, $decayMinutes = 1)
    {
        // Apply the throttle
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;
        return $key;
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key);
        }

        $this->limiter->hit($key, $decayMinutes);

        // Proceed with the request
        $response = $next($request);

        // You can add headers or additional logic to the response if needed

        return $response;
    }

    protected function buildResponse(string $key)
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => $retryAfter,
        ], 400);
    }
}
