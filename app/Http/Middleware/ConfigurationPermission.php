<?php

namespace App\Http\Middleware;

use App\Http\Resources\APIResource;
use App\Models\Permission;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ConfigurationPermission
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
        $permission = "can_set_".$request->get('name');        
        $user = auth('api-staff')->user();  
        $permissions = Permission::all();
        if($permissions->has($permission)){
            if($user->hasPermissionTo($permission)){
                return $next($request);
            }
            return new APIResource("Permission Denied", false, 401);
        }
        return $next($request);              
        
    }
}
