<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Tenancy;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Stancl\Tenancy\Exceptions\NotASubdomainException;

class InitializeTenancy extends IdentificationMiddleware
{
    protected $tenancy;
    protected $resolver;

    public function __construct(Tenancy $tenancy, DomainTenantResolver $resolver)
    {
        $this->tenancy = $tenancy;
        $this->resolver = $resolver;
    }

    public function handle($request, Closure $next)
    {
        try {
            if ($request->header('x-tenant')) {
                $hostname = $request->header('x-tenant');
                if ($this->isSubdomain($hostname)) {
                    $subdomain = $this->makeSubdomain($hostname);                
                    return $this->initializeTenancy($request, $next, $subdomain);
                } else {
                    return $this->initializeTenancy($request, $next, $hostname);
                }
            }

            
            return app(InitializeTenancyByDomainOrSubdomain::class)->handle($request, $next);
            
        }catch (\Exception $e) {
            
            return response()->json(['error' =>$e->getMessage()], 400);
        }catch (TenantCouldNotBeIdentifiedException $e) {          

            return response()->json(['error' => 'Tenant could not be identified:'. $e->getMessage()], 400);
        }
    }

    protected function isSubdomain(string $hostname): bool
    {
        return Str::contains($hostname, '.') && !$this->isCentralDomain($hostname);
    }

    protected function isCentralDomain(string $hostname): bool
    {
        return in_array($hostname, config('tenancy.central_domains'));
    }

    protected function makeSubdomain(string $hostname)
    {
        $parts = explode('.', $hostname);

        if (count($parts) === 1 || filter_var($parts[0], FILTER_VALIDATE_IP)) {
            return new NotASubdomainException($hostname);
        }

        return $parts[0];
    }
}
