<?php

namespace App\Http\Controllers;

use App\Http\Resources\APIResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                'country_id'=>160,//Nigeria
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
            $this->generateTenantKeys($tenant);
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
 

    protected function generateTenantKeys(Tenant $tenant)
    {
        // Specify the source paths
        $sourcePathPublic = __DIR__ . '/../../../storage/oauth-public.key';
        $sourcePathPrivate = __DIR__ . '/../../../storage/oauth-private.key';

        // Specify the destination directory
        $destinationDirectory = __DIR__ . '/../../../storage/' . $tenant->id;

        // Create the destination directory if it doesn't exist
        if (!is_dir($destinationDirectory)) {
            mkdir($destinationDirectory, 0755, true);
        }

        // Specify the destination paths
        $destinationPathPublic = $destinationDirectory . '/oauth-public.key';
        $destinationPathPrivate = $destinationDirectory . '/oauth-private.key';

        // Copy the keys to the tenant's directory
        copy($sourcePathPublic, $destinationPathPublic);
   /*      if (file_exists($sourcePathPublic)) {
        } else {
            // Handle the case where the source public key doesn't exist
            // (throw an exception, log an error, etc.)
        } */
        copy($sourcePathPrivate, $destinationPathPrivate);
/* 
        if (file_exists($sourcePathPrivate)) {
        } else {

            // Handle the case where the source private key doesn't exist
            // (throw an exception, log an error, etc.)
        } */
    }

    public function webhookFlutter(Request $request){
     
        $secretKey = env('APP_ENV') == 'production' ? config('ashlab.payment_gateways.flutterwave.live_secret_key') : config('ashlab.payment_gateways.flutterwave.test_secret_key');            
        /* $signature = $request->header('verif-hash');
        if (!$signature || ($signature !== $secretKey)) {
            // This request isn't from Flutterwave; discard
            Log::info("  ");
            abort(401);
        }     */ 
        $payload = $request->all();
        try{
            $transactionId = $payload['transaction_id'];
            $flwseck = env('APP_ENV') == 'production' ? config('ashlab.payment_gateways.flutterwave.live_secret_key') : config('ashlab.payment_gateways.flutterwave.test_secret_key');            
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $flwseck,
                "Cache-Control" => "no-cache"
            ])->get("https://api.flutterwave.com/v3/transactions/$transactionId/verify")->body();
            $response = json_decode($response);                    
            if($response?->status == 'success'){
                self::updatePaymentRecord($response);
                return view('transactions.completed');
            }else{
                throw new \Exception($response->message);                        
            }        
        }catch(\Exception $e){

        }
    }

    static public function updatePaymentRecord($response){
         
        $tenant = Tenant::find($response->data?->meta?->consumer_id);
        $tenant->run(function() use ($response){                
            $payment = Payment::where('ourTrxRef', $response?->data?->tx_ref)->first();
            if($response->data->amount >= $payment->amount){
                $payment->status = 'successful';
            }
            $payment->paid_amount = $response->data->amount;
            $payment->save();                    
            $payment->invoice->status = 'paid';
            $payment->payment_mode = 'flutterwave';
            $payment->invoice->payment_channel = 'gateway';
            $payment->gateway_response = json_encode($response->data);
            if($response->data->flw_ref??false){
                $payment->payment_reference = $response->data->flw_ref;
            }
            $payment->paid_at = Carbon::parse($response->data->created_at?? now())->format('Y-m-d h:i:s');
            $payment->invoice->save();
        });
    }
}
