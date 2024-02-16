<?php

namespace App\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;

class TenantController extends Controller
{
    public function createSchool(Request $request)
    {
        // Validate the incoming request data
        
        try {
            $validatedData = $request->validate([
                /* 'school_name' => 'required|string',
                'domain' => 'required|string', */
                'db_name' => 'required|string|unique:tenants,tenancy_db_name',
            ]);            
            
            // Create a new tenant                
            $tenantData = [
                'school_name' => $request->get('school_name'),
                'short_name' => $request->get('short_name') ?? '',
                'domain' => $request->get('domain'),
                'tenancy_db_name' => $request->get('db_name')
            ];
            
            $domain = $request->get('domain');
            
            // Find the tenant by domain, if it exists
            $tenant = Tenant::where('domain', $domain)->first();
            
            if ($tenant) {
                // If the tenant exists, update its details
                $tenant->update($tenantData);
            } else {
                // If the tenant doesn't exist, create a new one
                $tenant = Tenant::create($tenantData);
                // Associate the domain with the newly created tenant
                $tenant->domains()->create(['domain' => $domain]);
            }
            

            $tenant->run(function(){
                Artisan::call('db:seed', [
                    '--class' => 'DatabaseSeeder',
                ]);               
            });
            return new APIResource('Tenant created successfully', false, 200);
        } catch (\Exception $e) {            
            return $e;
            return response()->json(['error' =>json_encode($e)], 500);
        }catch(\Illuminate\Validation\ValidationException $e){            
            return $e;
            return response()->json(['error' => json_encode($e)], 500);
        }

        return response()->json(['message' => 'Tenant created successfully'], 200);
    }
}
