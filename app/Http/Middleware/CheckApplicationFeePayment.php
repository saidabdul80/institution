<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckApplicationFeePayment
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
        $user = auth('api-applicants')->user();

        if ($user && $user->is_imported && !$user->application_fee_paid) {
            // Allow access to payment-related routes and profile
            $allowedRoutes = [
                'api/applicants/self',
                'api/applicants/logout',
                'api/applicants/payments',
                'api/applicants/pay_application_fee',
                'api/applicants/payment_status'
            ];

            $currentPath = $request->path();

            // Check if current route is allowed
            foreach ($allowedRoutes as $allowedRoute) {
                if (str_contains($currentPath, $allowedRoute)) {
                    return $next($request);
                }
            }

            // Block access to other routes until payment is made
            return response()->json([
                'message' => 'Application fee payment required. Please pay your application fee to continue.',
                'error' => true,
                'requires_payment' => true,
                'status_code' => 402
            ], 402);
        }

        return $next($request);
    }
}
