<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Lga;
use App\Models\MDA;
use App\Jobs\SendSMS;
use App\Models\Agent;
use App\Models\Ticket;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Jobs\QueueMail;
use App\Models\Payment;
use App\Models\Staffer;
use App\Models\UbtCache;
use App\Enums\RevenueType;
use App\Models\TaxInvoice;
use App\Enums\TicketStatus;
use App\Models\Beneficiary;
use Illuminate\Support\Str;
use App\Models\PaymentShare;
use Illuminate\Http\Request;
use App\Models\Configuration;
use App\Models\RevenueSource;
use App\Models\PaymentGateway;
use App\Enums\RevenueFrequency;
use App\Enums\Status;
use App\Models\PaymentSpliting;
use App\Enums\TransactionStatus;
use App\Models\BusinessStructure;
use App\Models\CorporateTaxPayer;
use App\Models\IncomeDeclaration;
use App\Models\IndividualTaxPayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\TaxableBusinessAsset;
use Illuminate\Support\Facades\Http;
use App\Models\VendorCollectionPoint;
use App\Models\VendorEnrollmentPoint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\BusinessAssetsCompliance;
use App\Models\CorporateTaxPayerEmployee;
use App\Models\InitiatedPaymentTaxInvoice;

use App\Http\Resources\ConfigurationResource;
use App\Http\Controllers\BeneficiaryController;
use App\Models\Applicant;
use App\Models\TicketCollection;
use Laravel\Passport\Token;

class Util
{
    /**
     * Upload a file to S3 and return its URL.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    public static function upload($file,$key, $dir = "public", $useFileName = false)
    {
        try {
            $filePath = Storage::disk('s3')->put($dir ."/".$key. "/" . date('Y') . "/" . date('M'), $file);

            if ($filePath) {
                return $filePath;
            } else {
                Log::info("Failed to upload the file.");
            }
        } catch (\Exception $e) {
            Log::error('Error uploading file to S3: ' . $e->getMessage());
            return '';
        }
    }

    public static function deleteUpload($path)
    {
        try {
            $filePath = Storage::disk('s3')->delete($path);
        } catch (\Exception $e) {
            Log::error('Error uploading file to S3: ' . $e->getMessage());
            return '';
        }
    }


    public static function uploader($file, $key='storage'){
        return $file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? self::upload($file, $key, 'private') : $file;
    }

    /**
     * Generate a pre-signed URL for a file on S3.
     *
     * @param string $key
     * @param int $minutes
     * @return string
     */
    public static function publicUrl($relativePath, $minutes = 60)
    {
        try {
            // Ensure the relative path does not start with a leading slash
            $key = ltrim($relativePath, '/');
    
            // Log the key to ensure it's correct
            
    
            // Get the S3 client directly
            $client = Storage::disk('s3')->getClient();
            $expiry = "+{$minutes} minutes";
    
            // Prepare the GetObject command
            $command = $client->getCommand('GetObject', [
                'Bucket' => config('default.aws.bucket') ,
                'Key' => $key,
            ]);
    
            // Create a presigned request
            $request = $client->createPresignedRequest($command, $expiry);
            
            // Return the presigned URL
            return (string) $request->getUri();
        } catch (\Exception $e) {
            // Log the exception message
            // Log::error('Error generating presigned URL: ' . $e->getMessage());
            return "";
        }
    }
    
    /**
     * Upload a file from a request and return the updated request data with the file URL.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $fileName
     * @return array
     */
    public static function getRequestUploadedData(Request $request, $fileName = 'value')
    {
        $data = $request->all();
        $fileNames = is_array($fileName) ? $fileName : [$fileName];        
        
        foreach ($fileNames as $name) {
            if ($request->hasFile($name)) {
                $file = $request->file($name);
               // Log::info('Uploading file:', ['name' => $file->getClientOriginalName(), 'mime' => $file->getClientMimeType()]);
                
                $pictureUrl = self::upload($file, $name);                
                if (empty($pictureUrl)) {
                    Log::info("Failed to upload the file or generate the URL.");
                }
    
                $data[$name] = $pictureUrl;
            }
        }
    
        foreach ($data as $key => &$value) {
            if ($value == 'null') {
                $data[$key] = null;
            }
        }
    
        return $data;
    }
    
    public static function getUploadPath($url) {
        // Parse the URL to get its components
        $parsedUrl = parse_url($url);
    
        // Check if the path component exists
        if (isset($parsedUrl['path'])) {
            // Remove the leading slash from the path
            $key = ltrim($parsedUrl['path'], '/');
            
            // Check if the key contains query parameters and remove them
            if (isset($parsedUrl['query'])) {
                $key = explode('?', $key)[0];
            }
    
            return $key;
        }
        return null;
    }

    public static function getConfigValue($name)
    {
        $config = Configuration::where('name', $name)->first();
        if ($config) {
            if($config->field_type == 'image' || $config->field_type == 'file'){
                return self::publicUrl($config->value);
            }
            return $config->value;
        }
        return null;
    }

   

    public static function sendSMS($to, $message, $type="sms")
    {
        $config = config('default.sms');

        if (empty($config) || !isset($config['api_key']) || !isset($config['from'])) {
            Log::error('SMS configuration is incomplete.');
            return;
        }

        $payload =$config;
    //    Log::info($payload);
        $payload['to'] = $to;
        $payload['sms'] = config('default.title')."\n" . $message;

        $route = "/api/sms/send";        
        if($type=="bulk"){
            $route = "/api/sms/send/bulk";        
        }
        try {
            $response = Http::post(config('default.sms_base_url').$route, $payload);

            if ($response->successful()) {
              //  Log::info('SMS sent successfully: ' . $response->body());
            } else {
                Log::error('SMS sending failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('SMS sending exception: ' . $e->getMessage());
        }
    }

    public static function verifyPhoneNumber($request)
    {
        $user = $request->user();
        
        if(is_null($user)){
            abort(401, "Unauthorized");
        }

        $user = get_class($user)::where('id', $user->id)
            ->where('phone_number_otp', $request->otp)
            ->where(function ($query) use ($request) {
                $query->where('phone_number_1', $request->phone_number)
                    ->orWhere('phone_number_2', $request->phone_number);
            })
            ->where('phone_number_otp_expires_at', '>', now())
            ->first();
    
        if ($user) {            
            $column_name = $user->phone_number_1 === $request->phone_number ? 'phone_number_1' : 'phone_number_2';
            $user->update([
                $column_name . '_verified_at' => now(),
                'phone_number_otp' => null,
                'phone_number_otp_expires_at' => null
            ]);
            return "Phone number verified successfully.";
        }
    
        return false;
    }
    
    /* 
    public static function clearGarbageUsers(){
        IndividualTaxPayer::whereNull('email_verified_at')->where('otp_expires_at', '<', now())->delete();
        CorporateTaxPayer::whereNull('email_verified_at')->where('otp_expires_at', '<', now())->delete();
    } */
    // static public function processPaymentCompletion($payment,$totalPaid){    
    //     $invoices = $payment->taxInvoices;
    //     foreach ($invoices as $invoice) {
    //         if ($totalPaid > 0) {
    //             if ($totalPaid >= $invoice->amount) {
    //                 self::revenueSubHeadResolver($invoice, $totalPaid);
    //                 $invoice->update([
    //                     'status' => 'paid'
    //                 ]);
    //                 $totalPaid -= $invoice->amount;
    //                 if($invoices->count() == 1 && $totalPaid > $invoice->amount){
    //                     $wallet = Wallet::getWalletWithInvoice($invoice);
    //                     $wallet->credit($totalPaid - $invoice->amount);
    //                 }
    //             } else {                        
    //                 if ($invoices->count() == 1) {
    //                     $invoice->update([
    //                         'status' => 'part_paid'
    //                     ]);
    //                 } else {
    //                     $wallet = Wallet::getWalletWithInvoice($invoice);
    //                     $wallet->credit($totalPaid);
    //                 }
    //                 $totalPaid = 0;
    //             }
    //         } else {
    //             InitiatedPaymentTaxInvoice::where([
    //                 'payment_id' => $payment->id,
    //                 'invoice_id' => $invoice->id,
    //             ])->delete();
    //         }
    //     }
    // }
    static public function processPaymentCompletion($payment, $totalPaid = 0, $date = null, $time = null) 
    {
        // Get the single invoice associated with this payment
        $invoice = $payment->invoice;
        
        if (!$invoice) {
            Log::error("No invoice found for payment ID: {$payment->id}");
            return;
        }  
        // Calculate remaining amount to apply
        $remainingAmount = max(0, $totalPaid - ($invoice->paid_amount ?? 0));
        
        if ($remainingAmount <= 0) {
            Log::info("No payment needed - invoice already fully paid for payment ID: {$payment->id}");
            return;
        }

        $newPaidAmount = ($invoice->paid_amount ?? 0) + $totalPaid;
        
        // Update invoice status based on payment
        $updateData = [
            'paid_amount' => $newPaidAmount,
            'status' => $newPaidAmount >= $invoice->amount ? 'paid' : 'part paid'
        ];

        self::resolvePayment($payment, $invoice, $totalPaid);
        $invoice->update($updateData);

        $payment->update([
            // 'paid_amount' => $newPaidAmount, ## it is already updated with the actual amount paid
            'paid_time' => $time ?? Carbon::now()->format('H:i:s'),
            'date' => $date ?? Carbon::now()->format('Y-m-d')
        ]);

        if ($payment->gateway != 'wallet' && Util::getConfigValue('enable_split_payment') == 'true') {
            PaymentSpliting::where('payment_id', $payment->id)->update(['status' => 1]);
        }
        return $payment;
    }

    private static function resolvePayment($payment)
    {   
        $paymentCategory =$payment->invoice->invoiceType->payment_category;

        switch ($paymentCategory->short_name) {
            case 'application_fee':
                $applicant = Applicant::where('id', $payment->invoice->owner_id)->first();
                $applicant->application_fee_paid = true;
                //$applicant->application_fee = 'paid';
                $applicant->application_fee_paid_at = now();
                $applicant->save();
                break;
            case 'registration_fee':
                
                break;
            case 'acceptance_fee':

                break;
            default:
                break;
        }
    }

    static private function prepareShareLogic($array, $invoice, $payment_id, $paidAmount)
    {
        $source = $invoice->invoiceType->revenueSubHead->revenueHead->revenueSource;
        $state = Beneficiary::where('name', 'IRS')->first();
        $ubt = Beneficiary::where('name', 'UBT')->first();

        $vendor = null;

        // Determine vendor if present
        if ($invoice->issuer_type == Vendor::class || $invoice->issuer_type == Agent::class) {
            if ($invoice->issuer_type == Agent::class) {
                $vendor = Agent::with('vendor')->where('id', $invoice->issuer_id)->first()?->vendor;
            } else {
                $vendor = Vendor::find($invoice->issuer_id);
            }
        } elseif ($invoice->issued_through_type == Vendor::class || $invoice->issued_through_type == Agent::class) {
            if ($invoice->issued_through_type == Agent::class) {
                $vendor = Agent::with('vendor')->where('id', $invoice->issued_through_id)->first()?->vendor;
            } else {
                $vendor = Vendor::find($invoice->issued_through_id);
            }
        }

        $data = [];

        // Deduct vendor share first if vendor exists
        if ($vendor) {
            $vendorShareAmount = ($vendor->share / 100) * $paidAmount;
            $paidAmount -= $vendorShareAmount;

            $data[] = [
                'payment_id' => $payment_id,
                'beneficiary_type' => 'VENDOR',
                'beneficiary_id' => $vendor->id,
                'share_amount' => $vendorShareAmount
            ];

           Wallet::getWalletWithVendor($vendor)->credit($vendorShareAmount,$vendor->share.' share of collection');
        }

        // Calculate UBT share from remaining amount
        $ubtShareAmount = ($ubt->share / 100) * $paidAmount;

        $lgaShareAmount = null;
        $stateShareAmount = null;

        // Determine shares based on source type
        if ($source->type == 'LGA') {
            $lga = Beneficiary::where('lga_id', $source->id)->first();
            $stateShareAmount = ($lga->state_share / 100) * $paidAmount;
            $lgaShareAmount = ($lga->share / 100) * $paidAmount;
        } else {
            $stateShareAmount = ($state->share / 100) * $paidAmount;
        }

        // Add shares to data array
        if ($lgaShareAmount) {
            $data[] = [
                'payment_id' => $payment_id,
                'beneficiary_type' => 'LGA',
                'beneficiary_id' => $source->id,
                'share_amount' => $lgaShareAmount
            ];
        }

        $data[] = [
            'payment_id' => $payment_id,
            'beneficiary_type' => 'UBT',
            'beneficiary_id' => $ubt->id,
            'share_amount' => $ubtShareAmount
        ];

        $data[] = [
            'payment_id' => $payment_id,
            'beneficiary_type' => 'STATE',
            'beneficiary_id' => $state->id,
            'share_amount' => $stateShareAmount
        ];

        return array_merge($array, $data);
    }

    static public function revenueSubHeadResolver($invoice, $paidAmount)
    {
        $revenueSubHead = $invoice->invoiceType->revenueSubHead;
        switch ($revenueSubHead->revenue_type) {
            case RevenueType::DEFAULT:
            case RevenueType::PENALTY:
                // NO logic required              
            case RevenueType::PAYE:
                CorporateTaxPayerEmployee::where('id', $invoice->related_id)->status(['payment_status'=>'paid','tax_amount'=> $paidAmount]);
                break;
            case RevenueType::ASSETS:
                $compliance = BusinessAssetsCompliance::find($invoice->related_id);

                // $complianceDate = BusinessAssetsCompliance::where('taxable_business_asset_id', $invoice->related_id)
                //                 ->latest('compliance_date')->first();

                if (empty($compliance)) {
                    // Log::error('Taxable Business Asset not found for Invoice ID: ' . $invoice->id);
                    abort(422,'Taxable Business Asset not found for Invoice ID: ' . $invoice->invoice_number);
                }
                $lastComplianceDate = is_null($compliance?->compliance_date) ? Carbon::parse($compliance->created_at) : Carbon::parse($compliance->compliance_date);
                $frequency = RevenueFrequency::getKey($invoice->invoiceType->frequency);
                $rate = (int) $invoice->invoiceType->template;
                $date = '';

                if ($frequency === 'DAILY') {
                    $date = $lastComplianceDate->copy()->addDays(floor($paidAmount / $rate));
                } elseif ($frequency === 'WEEKLY') {
                    $date = $lastComplianceDate->copy()->addWeeks(floor($paidAmount / $rate));
                } elseif ($frequency === 'MONTHLY') {
                    $date = $lastComplianceDate->copy()->addMonths(floor($paidAmount / $rate));
                } elseif ($frequency === 'YEARLY') {
                    $date = $lastComplianceDate->copy()->addYears(floor($paidAmount / $rate));
                }
            
                $compliance->update([
                  'compliance_date' => $formattedDate = $date->format('Y-m-d')
                ]);
                // if ($date instanceof Carbon) {
                //     $formattedDate = $date->format('Y-m-d');
                    
                //     // Update or create compliance record
                //     BusinessAssetsCompliance::updateOrCreate(
                //         ['taxable_business_asset_id' => $invoice->related_id, 'revenue_sub_head_id' => $invoice->revenue_sub_head_id],
                        
                //     );
                // } else {
                //     Log::info('Could Not complete Transaction Process: ' . json_encode($invoice));
                // }
                break;
            case RevenueType::TICKETS:
                
                $ticket = Ticket::find($invoice->related_id);
                $ticket->status = TicketStatus::PAID;
                $ticket->save();

                $tickets = [];
                $purchaser = $ticket->purchaser;
                for ($i = 0; $i < $ticket->total_ticket; $i++) {
                    $tickets[] = [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->code . '/' . strtoupper(Str::random(3)),
                        'generator_type' => get_class($purchaser),
                        'generator_id' => $purchaser->id,
                        'allocated_to_type' => $purchaser instanceof Staffer ? get_class($purchaser) : null,
                        'allocated_to_id' => $purchaser instanceof Staffer ? $purchaser->id : null,
                        'amount' => $ticket->total_amount / $ticket->total_ticket,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                TicketCollection::insert($tickets);
                break;
            case RevenueType::TCC:
               // self::tccRelatedPaymentResolver($invoice, $paidAmount);
                // $data = IncomeDeclaration::find($invoice->related_id);
                // $data->paid_tax += $paidAmount;
                // $data->save();
                // $data->refresh();
                // if($data->paid_tax >= $data->taxable_icome){
                //     $data->update(['issue_date' => Carbon::now()->format('Y-m-d')]);
                //     $individual = $data->info;
                //     SendSMS::dispatch(
                //         validate_phone_number($individual->phone_number_1),
                //         "Dear " . $individual->full_name . "\n" .
                //             "Your TCC ($data->certificate_number) is ready for download\n"
                //     );
                //     $invoiceMailData = $individual->toArray();
                //     $invoiceMailData['message'] = "Your TCC ($data->certificate_number) is ready for download";
                //     QueueMail::dispatch($invoiceMailData, 'tcc_mail', "TCC Application Approved");
                // }
                break;
            case RevenueType::DIRECT_ASSESSMENT:
                // if($invoice->related_type == IncomeDeclaration::class){
                //     self::tccRelatedPaymentResolver($invoice, $paidAmount);
                // }
            default:
                break;
        }
    }

    static public function tccRelatedPaymentResolver($invoice, $paidAmount){
        $data = IncomeDeclaration::find($invoice->related_id);
        $data->update(['paid_tax' => $data->paid_tax + $paidAmount]);
        $data->refresh();
        if($data->paid_tax >= $data->taxable_icome){
            $data->update(['issue_date' => Carbon::now()->format('Y-m-d')]);
            $individual = $data->info;
            SendSMS::dispatch(
                validate_phone_number($individual?->phone_number_1),
                "Dear " . $individual?->full_name . "\n" .
                    "Your TCC ($data->certificate_number) is ready for download\n"
            );
            $invoiceMailData = $individual->toArray();
            $invoiceMailData['message'] = "Your TCC ($data->certificate_number) is ready for download";
            QueueMail::dispatch($invoiceMailData, 'tcc_mail', "TCC Application Approved");
        }
    }

    static public function validateAndGetRevenueSource($user, $revenue_source_type, $revenue_source_id){
        $filter = [];
        if ($user->user_type == 'staff') {
            if( str_contains(strtoupper($user->category),'MDA')){
                $filter['revenue_source_type'] = Util::getMDASoureModel($user->mda_type);
                $filter['revenue_source_id'] = $user->mda_id;
            }
            if(authorize($user, CAN_ACCESS_ONLY_ISSUED_INVOICES,null, false)){
                $filter['issuer_type'] = $user::class;
                $filter['issuer_id'] = $user->id;
            }

            // if (!authorize($user, CAN_VIEW_STATE_ANALYTIC, null, false) && authorize($user, CAN_VIEW_LGA_ANALYTIC, null, false)) {
            //     $revenue_source_type =  self::getMDASoureModel('LGA');
            //     $revenue_source_id = $revenue_source_id ?? Util::getUserLgaIDs($user);
            // } else if (authorize($user, CAN_VIEW_STATE_ANALYTIC, null, false) && !authorize($user, CAN_VIEW_LGA_ANALYTIC, null, false)) {
            //     $revenue_source_type =  self::getMDASoureModel('STATE');
            // } else {
            //     $revenue_source_type = self::getMDASoureModel($revenue_source_type);
            // }
            // Add additional filters based on the user type and revenue source type
            // $filter["revenue_source_id"] = $revenue_source_id;
            // $filter["revenue_source_type"] = $revenue_source_type;
        }else{
            $filter[$user->user_type] = $user->id;
        }

        return $filter;
    }

    static public function getTimeAt($dateTime)
    {        
        $created_at = Carbon::parse($dateTime);
        // Get the current time as a Carbon instance
        $current_time = Carbon::now();
        
        // Calculate the time difference between now and the 'created_at' timestamp
        $diff_seconds = floor($created_at->diffInSeconds($current_time));
        $diff_minutes = floor($created_at->diffInMinutes($current_time));
        $diff_hours = floor($created_at->diffInHours($current_time));
        
        // Format the output based on the time difference
        if ($diff_seconds < 60) {
            $time_ago = "$diff_seconds second" . ($diff_seconds > 1 ? 's' : '') . " ago";
        } elseif ($diff_minutes < 60) {
            $time_ago = "$diff_minutes minute" . ($diff_minutes > 1 ? 's' : '') . " ago";
        } elseif ($diff_hours < 12) {
            $time_ago = "$diff_hours hour" . ($diff_hours > 1 ? 's' : '') . " ago";
        } else {
            // If more than a day ago, show the time in '2:30 PM' format
            $time_ago = $created_at->format('g:i A');
        }
        
        return $time_ago;   
    }

    static public function withStrictModeOff(callable $callback)
    {
        try {
            // Turn off strict mode
            DB::statement('SET SESSION sql_mode = ""');

            // Execute the callback
            $result = $callback();
            // Turn on strict mode
            DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,STRICT_ALL_TABLES"');

            return $result;
        } catch (\Exception $e) {
            // Handle any exceptions that occur during execution
            DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,STRICT_ALL_TABLES"');
            throw $e;
        }
    }

    static public  function getMDASoureModel($name){
        if (class_exists($name)) {
            return $name;
        }
    
        return MDA::where('name', $name)->first()?->model;
    }

    static public function getUserLgaIDs($user){
        return Lga::where('state_id', Util::getConfigValue('state_id'))->whereIn('name', $user->abilities)->pluck('id')->toArray();
    }

    static public function calculatePercentage($total_revenue, $total_revenue_prev){
        $percentage = 0;
        if ($total_revenue_prev != 0) {
            $percentage = (($total_revenue - $total_revenue_prev) / $total_revenue_prev) * 100;
        } elseif ($total_revenue > 0) {
            $percentage = 100;
        }

        return round($percentage,2);
    }
    static public function reduceImageSize($imagePath){
        // Increase execution time limit
        set_time_limit(120); // Increase the limit to 120 seconds
      //  Log::info('image test --->'.$imagePath);
        list($width, $height) = getimagesize($imagePath);
    
        // Handle different image types
        $imageType = exif_imagetype($imagePath);
        if ($imageType == IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg($imagePath);
        } elseif ($imageType == IMAGETYPE_PNG) {
            $image = @imagecreatefrompng($imagePath);
        } elseif ($imageType == IMAGETYPE_GIF) {
            $image = imagecreatefromgif($imagePath);
        } else {
            throw new \Exception('Unsupported image type.');
        }
    
        // Calculate new dimensions
        $newWidth = 1172; // Desired width
        $newHeight = ($height / $width) * $newWidth;
    
        // Create a new empty image with the new dimensions
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    
        // Copy the original image into the resized image
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
        // Start output buffering to capture the image data
        ob_start();
        imagejpeg($resizedImage, null, 100);
        $imageData = ob_get_contents();
        ob_end_clean();
    
        // Free up memory
        imagedestroy($image);
        imagedestroy($resizedImage);
    
        // Return the image as a base64-encoded string
        return 'data:image/jpeg;base64,' . base64_encode($imageData);
    }
    

    static public function throwError(\Exception $e){
        if(config('app.env') == 'local'){
          return $e;       
        }
        return response()->json('something went wrong', 400);
    }

    static public function GenericUtils($emailOrPhone, $expired_at, $code,$user_id=null,$user_type=null, $type='verify'){
        $uniqueToken = Str::random(40);
        return UbtCache::updateOrCreate([
            'email'=>$emailOrPhone
        ],[
            'token'=> $uniqueToken,
            'expires_at'=> $expired_at,
            'data'=>  $code,
            'user_type'=>$user_type,
            'type'=>$type,
            'user_id'=>$user_id
        ]);
    }
    
    static public function OTPUtils($user, $expired_at, $code, $type='verify'){

        $user_type =  get_user_type($user->user_type);
        $uniqueToken = Str::random(40);
        UbtCache::updateOrCreate([
            'email'=>$user->email
        ],[
            'token'=> $uniqueToken,
            'expires_at'=> $expired_at,
            'data'=>  $code,
            'user_type'=>$user_type,
            'type'=>$type,
            'user_id'=>$user->id
        ]);
       // Cache::put($uniqueToken, [$user->email,$user_type], now()->addMinutes(10));

        $data = [
            "otp" => $code,
            "expires_at" => $expired_at,
            "email" => $user->email,
            "token"=>$uniqueToken,
            "user_type" => $user_type,
            "name" => $user->business_name ?? $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name
        ];

        return $data;
    }

    static public function imageUrlToBase64($url)
    {
        try{

            $image = file_get_contents($url);
            if ($image !== false){
                return 'data:image/jpg;base64,'.base64_encode($image);
                
            }
        }catch(\Exception $e){
            if(self::getConfigValue('show_major_errors') == 'true'){
               // abort(400, "Failed to open stream");
            }else{
                Log::error("Failed to open stream: HTTP request failed! HTTP/1.1 404 Not Foun: Util::imageUrlToBase64");
            }
        }
    }

    static public function invoiceInitiated($invoice){
        $link = config('default.portal.domain') . '/' . 'verify/' . $invoice->invoice_number;
        $taxable = $invoice->taxable;
        $phone_number = $taxable?->phone_number?? $taxable->phone_number_1;
        if (!empty($phone_number)) {
            SendSMS::dispatch(
                validate_phone_number($phone_number),  
                "Dear " . $taxable->full_name . "\n" .
                "A payment of {$invoice->amount} has been initiated on your account with invoice number ({$invoice->invoice_number}).\n. Kindly use the link below to proceed with payment\n" .
                $link
            );
        }
        
        $invoiceMailData = $taxable->toArray();
        $invoiceMailData['email'] = $invoiceMailData['email'] ?? config('default.email');
        $invoiceMailData['link'] = $link;
        $invoiceMailData['message'] = "
                <p>
                    A payment of {$invoice->amount} has been initiated on your account with invoice number ({$invoice->invoice_number}).
                </p>
                <p>
                    Kindly use the link below to proceed with payment:
                </p>
                <a href='$link'>Click to Continue</a>
            ";

        QueueMail::dispatch($invoiceMailData, 'invoice_initiated', "GIRS Invoice Created");

    }
   
    private static function lastSubaccountShare($subaccounts){

        if($subaccounts->sum('share') != 100){
            $totalShare =$subaccounts->sum('share');
            return (100 - $totalShare) + $subaccounts->last()['share'];
        }
        return $subaccounts->last()['share'];
    }

  
    public static function getSplitCode($payment, $amount)
    {
        $header = [
            "Authorization" => "Bearer " . config('paystack.secretKey'),
            "Content-Type" => "application/json",
        ];
    
        // Use the reusable method to prepare subaccounts
        $filtered_subaccounts = self::prepareSubaccounts($payment, $amount,false, "paystack");
        //Log::info("subaccount Array filter : " . json_encode($filtered_subaccounts->last()));
        $response = Http::withHeaders($header)->post("https://api.paystack.co/split", [
            'name' => 'Halfsies',
            'type' => "percentage",
            'currency' => "NGN",
            "bearer_type"=> "subaccount",
            "bearer_subaccount"=> $filtered_subaccounts->last()['subaccount'],
            'subaccounts' => $filtered_subaccounts->toArray(),
        ]);
        
    
        // return abort(400, $response->body());
        if (!$response->successful()) {
            $filtered_subaccounts = self::prepareSubaccounts($payment, $amount, true, "paystack");
            //Log::info("subaccount Array filter : " . json_encode($filtered_subaccounts->last()));
            $response = Http::withHeaders($header)->post("https://api.paystack.co/split", [
                'name' => 'Halfsies',
                'type' => "percentage",
                'currency' => "NGN",
                "bearer_type"=> "subaccount",
                "bearer_subaccount"=> $filtered_subaccounts->last()['subaccount'],
                'subaccounts' => $filtered_subaccounts->toArray(),
            ]);

            if (!$response->successful()) {
                abort(400, $response->body());
            }
        }
    
        return $response->json()['data']['split_code'];
    }

    public static function lineItems($payment, $amount)
    {
        $id = 0;

        // Use the reusable method to prepare subaccounts
        $filtered_subaccounts = self::prepareSubaccounts($payment, $amount, false, "remita");

        // Prepare line items for return
        $lineItems = $filtered_subaccounts->map(function ($subaccount) use (&$id) {
            return [
                'lineItemsId' => 'itemid' . $id++,
                'beneficiaryName' => $subaccount['account_name'],
                'beneficiaryAccount' => $subaccount['account_number'],
                'bankCode' => $subaccount['bank_code'],
                'beneficiaryAmount' => round( $subaccount['amount'], 2),
                'deductFeeFrom' => $subaccount['deductFeeFrom'],
            ];
        });

        return $lineItems;
    }

    public static function prepareSubaccounts($payment, $amount, $new = false, $gateway="paystack")
    {
        $invoices = $payment->taxInvoices;

        $subaccounts = self::splitPayments($invoices, $amount,$gateway);

        // Prepare subaccounts with updated subaccount code
        $prep_subaccounts = $subaccounts->map(function ($subaccount) use($new) {

            // $subaccount_code = app(BeneficiaryController::class)->updateSubaccount($subaccount, $subaccount['share'], $new);    
            // //Log::info("subaccount Array : " . json_encode($subaccount));
            // if (!$subaccount_code && !$subaccount['subaccount_code']) {
            //     // Record the filtered-out subaccount with collected_share as 0
            //     return [
            //         "id" => $subaccount['id'],
            //         "subaccount" =>null, // use the original subaccount
            //         "share" => $subaccount['share'],
            //         "collected_share" => 0,
            //         "account_name" => $subaccount['account_name'], // preserve original data
            //         "account_number" => $subaccount['account_number'],
            //         "bank_code" => $subaccount['bank_code'],
            //         "deductFeeFrom" => $subaccount['deductFeeFrom'],
            //         "amount" => $subaccount['amount'],
            //         "status" => $subaccount['status']
            //     ];
            // }

            return [
                "id" => $subaccount['id'],
                "subaccount" => $subaccount['subaccount_code'],
                "share" => $subaccount['share'],
                "collected_share" => $subaccount['share'], // initially set to the same as share
                "account_name" => $subaccount['account_name'], // preserve original data
                "account_number" => $subaccount['account_number'],
                "bank_code" => $subaccount['bank_code'],
                "deductFeeFrom" => $subaccount['deductFeeFrom'],
                "amount" => $subaccount['amount'],
                "status" => $subaccount['status']
            ];
        });

        // Filter out subaccounts with collected_share as 0
        $filtered_subaccounts = $prep_subaccounts->filter(function ($subaccount) {
            return $subaccount['collected_share'] != 0 && $subaccount['status'] == 1;
        });
        
        // Adjust the share of the last subaccount if the total share is not 100
        if($filtered_subaccounts->isEmpty()){
            return collect([]);
        }

        $last_share = self::lastSubaccountShare($filtered_subaccounts);
        $filtered_subaccounts = $filtered_subaccounts->toArray();
        $filtered_subaccounts[count($filtered_subaccounts) - 1]['share'] = $last_share;
        
        // Adjust collected_share for the last subaccount to ensure total is 100
        $collected_share_total = collect($filtered_subaccounts)->sum('collected_share');
        if ($collected_share_total != 100) {
            $filtered_subaccounts[count($filtered_subaccounts) - 1]['collected_share'] += (100 - $collected_share_total);
        }
        
        // Prepare data for PaymentSpliting 
        $paymentSpliting = $prep_subaccounts->map(function ($subaccount) use ($payment) {
            return [
                'payment_id' => $payment->id,
                'beneficiary_id' => $subaccount['id'],
                'share' => $subaccount['share'],
                'collected_share' => $subaccount['collected_share'],
                'status' => 0
            ];
        });
        
        // Create PaymentSpliting records
        PaymentSpliting::insert($paymentSpliting->toArray());

        return collect($filtered_subaccounts);
    }

     public static function lineItemsTemp($payment, $amount)
    {
        $id = 0;

        // Use the reusable method to prepare subaccounts
        $filtered_subaccounts = self::prepareSubaccountsTemp($payment, $amount, false, "remita");

        // Prepare line items for return
        $lineItems = $filtered_subaccounts->map(function ($subaccount) use (&$id) {
            return [
                'lineItemsId' => 'itemid' . $id++,
                'beneficiaryName' => $subaccount['account_name'],
                'beneficiaryAccount' => $subaccount['account_number'],
                'bankCode' => $subaccount['bank_code'],
                'beneficiaryAmount' => round( $subaccount['amount'], 2),
                'deductFeeFrom' => $subaccount['deductFeeFrom'],
            ];
        });

        return $lineItems;
    }

      private static function splitPaymentsTemp($invoices, $amountToPay,$gateway){

        $allSubaccounts = collect();
        $amountLeft = $amountToPay;

        foreach ($invoices as $invoice) {
            $invoiceAmount = $invoice->amount;
            
            
            if($amountLeft < $invoiceAmount){
                $invoiceAmount  = $amountLeft;
            }

            
            if( $invoiceAmount == 0){
                continue;
            }


            $subaccounts = self::prepareSubaccountsForSplit($invoice, $invoiceAmount, $gateway);

            $amountLeft -= $invoiceAmount;
            $allSubaccounts = $allSubaccounts->merge($subaccounts);
            
        }
        
       
        $totalAmount = $allSubaccounts->sum('amount');
        $distinctAccounts = [];
        foreach ($allSubaccounts as $subaccount) {
            if(empty($subaccount['bank_code'])){
                abort(422, 'Bank code is required');
            }
    
            if(empty($subaccount['account_number'])){
                abort(422, 'Account Number is required');
            }
            

            if (isset($distinctAccounts[$subaccount['name']])) {
                $distinctAccounts[$subaccount['name']]['amount'] += $subaccount['amount'];
            } else {
                $distinctAccounts[$subaccount['name']] = $subaccount;
            }
        }

        // if(count($distinctAccounts) == 1 && $distinctAccounts["IRS"]){
        //     $distinctAccounts["IRS"]["amount"] = $amountToPay;
        //     $distinctAccounts["IRS"]["share"] = 100;
        //     $totalAmount = $amountToPay;
        // }



        
        $ac = collect($distinctAccounts)->map(function ($subaccount) use ($totalAmount) {
            return [
                "id" => $subaccount['id'],
                "recipient_code" => $subaccount['recipient_code'],
                "subaccount_code" => $subaccount['subaccount_code']??null,
                "share" => ($subaccount['amount'] / $totalAmount) * 100,
                "amount" => $subaccount['amount'],
                "account_name" => $subaccount['account_name'],
                "account_number" => $subaccount['account_number'],
                "bank_code" => $subaccount['bank_code'],
                "currency" => $subaccount['currency'],
                "status" => $subaccount['status'],
                "deductFeeFrom"=>$subaccount['deductFeeFrom']
            ];
        })->values();
        return $ac;
    }
    
    public static function prepareSubaccountsTemp($payment, $amount, $new = false, $gateway="paystack")
    {
        $invoices = $payment->taxInvoices;

        $subaccounts = self::splitPaymentsTemp($invoices, $amount,$gateway);

        // Prepare subaccounts with updated subaccount code
        $prep_subaccounts = $subaccounts->map(function ($subaccount) use($new) {

            // $subaccount_code = app(BeneficiaryController::class)->updateSubaccount($subaccount, $subaccount['share'], $new);    
            // //Log::info("subaccount Array : " . json_encode($subaccount));
            // if (!$subaccount_code && !$subaccount['subaccount_code']) {
            //     // Record the filtered-out subaccount with collected_share as 0
            //     return [
            //         "id" => $subaccount['id'],
            //         "subaccount" =>null, // use the original subaccount
            //         "share" => $subaccount['share'],
            //         "collected_share" => 0,
            //         "account_name" => $subaccount['account_name'], // preserve original data
            //         "account_number" => $subaccount['account_number'],
            //         "bank_code" => $subaccount['bank_code'],
            //         "deductFeeFrom" => $subaccount['deductFeeFrom'],
            //         "amount" => $subaccount['amount'],
            //         "status" => $subaccount['status']
            //     ];
            // }

            return [
                "id" => $subaccount['id'],
                "subaccount" => $subaccount['subaccount_code'],
                "share" => $subaccount['share'],
                "collected_share" => $subaccount['share'], // initially set to the same as share
                "account_name" => $subaccount['account_name'], // preserve original data
                "account_number" => $subaccount['account_number'],
                "bank_code" => $subaccount['bank_code'],
                "deductFeeFrom" => $subaccount['deductFeeFrom'],
                "amount" => $subaccount['amount'],
                "status" => $subaccount['status']
            ];
        });

        // Filter out subaccounts with collected_share as 0
        $filtered_subaccounts = $prep_subaccounts->filter(function ($subaccount) {
            return $subaccount['collected_share'] != 0 && $subaccount['status'] == 1;
        });
        
        // Adjust the share of the last subaccount if the total share is not 100
        if($filtered_subaccounts->isEmpty()){
            return collect([]);
        }

        $last_share = self::lastSubaccountShare($filtered_subaccounts);
        $filtered_subaccounts = $filtered_subaccounts->toArray();
        $filtered_subaccounts[count($filtered_subaccounts) - 1]['share'] = $last_share;
        
        // Adjust collected_share for the last subaccount to ensure total is 100
        $collected_share_total = collect($filtered_subaccounts)->sum('collected_share');
        if ($collected_share_total != 100) {
            $filtered_subaccounts[count($filtered_subaccounts) - 1]['collected_share'] += (100 - $collected_share_total);
        }
        
        // Prepare data for PaymentSpliting 
        $paymentSpliting = $prep_subaccounts->map(function ($subaccount) use ($payment) {
            return [
                'payment_id' => $payment->id,
                'beneficiary_id' => $subaccount['id'],
                'share' => $subaccount['share'],
                'collected_share' => $subaccount['collected_share'],
                'status' => 0
            ];
        });

        return collect($filtered_subaccounts);
    }


    // public static function lineItems($payment, $amount)
    // {
    //     $id = 0;
    //     $invoices = $payment->taxInvoices;
    //     $lineItems = 
    //     self::splitPayments($invoices, $amount)->map(function ($subaccount) use (&$id) {
    //         return [
    //             'lineItemsId' => 'itemid'.$id++,  
    //             'beneficiaryName' => $subaccount['account_name'],
    //             'beneficiaryAccount' => $subaccount['account_number'],
    //             'bankCode' => $subaccount['bank_code'],
    //             'beneficiaryAmount' => number_format(($subaccount['amount']), 2, '.', ''),
    //             'deductFeeFrom' => $subaccount['deductFeeFrom'],
    //         ];
    //     });
    //     return $lineItems;
    // }

    // private static function splitPayments($invoice){
    //     $issuer = $invoice->issuer;
    
    //     $revenueSubHead = $invoice->revenueSubHead;
    //     $invoiceAmount = $invoice->amount;

    //     if(get_class($issuer) == Agent::class){
    //         $issuer = $issuer->vendor;
    //     }

    //     $beneficiaries = Beneficiary::where(function ($query) use ($issuer) {
    //         $query->where('beneficiary_type', get_class($issuer))
    //             ->where('beneficiary_id', $issuer->id);
    //     });

    //     if($invoice->revenue_source_type == Lga::class){
    //         $beneficiaries = $beneficiaries->orWhere(function ($query) use ($invoice) {
    //             $query->where('beneficiary_type', $invoice->revenue_source_type)
    //                 ->where('beneficiary_id', $invoice->revenue_source_id);
    //         });
    //     }
        
    //     $beneficiaries  = $beneficiaries->get();
    
    //     $subaccounts = $beneficiaries->map(function ($beneficiary) use ($revenueSubHead, $invoiceAmount) {
    //         $share = 0;
    //         switch ($beneficiary->beneficiary_type) {
    //             //case RevenueSource::class:
    //             case LGA::class:
    //                 $share = $revenueSubHead->mda_share;
    //                 break;
    //             case Vendor::class:
    //                 $share = $revenueSubHead->vendor_share;
    //                 break;
    //         }

    //         if ($revenueSubHead->share_type === 'fixed') {
    //             $share = ($share / $invoiceAmount) * 100;
    //         }
    
    //         return [
    //             "share" => $share,
    //             "name" => $beneficiary->name,
    //             "account_name" => $beneficiary->account_name,
    //             "account_number" => $beneficiary->account_number,
    //             "bank_code" => $beneficiary->bank_code,
    //             "currency" => $beneficiary->currency,
    //             "recipient_code" => $beneficiary->recipient_code,
    //         ];
    //     });
    
    //     $totalShare = $subaccounts->sum('share');
        

    //     $beneficiaries = Beneficiary::whereIn('name', ['IRS', 'UBT'])->get();
    //     $ubt = $beneficiaries->where('name', 'UBT')->first();
    //     $irs = $beneficiaries->where('name', 'IRS')->first();
    
    //     $irs_share = $revenueSubHead->irs_share;
    //     $ubt_share = $ubt->share;
    //     if ($revenueSubHead->share_type === 'fixed') {
    //         //$totalFixedShare = $revenueSubHead->mda_share + $revenueSubHead->irs_share + $revenueSubHead->vendor_share;
    //         $mda_vendor = $revenueSubHead->mda_share + $revenueSubHead->vendor_share;
    //         $mda_vendor_perc = ($mda_vendor / $invoiceAmount) * 100;
            
    //         if ((100 - $mda_vendor_perc) < $ubt->share) {
    //             $difference = 100 - ($totalShare + $ubt->share);
    //             $irs_share = $difference;
    //             $ubt_share =$ubt->share;
    //         }

    //     }else{
    //         if ((100 - $totalShare) < $ubt->share) {
    //             $difference = 100 - ($totalShare + $ubt->share);
    //             $irs_share = $difference;
    //             $ubt_share =$ubt->share;
    //         }
    //     }

    //     $subaccounts->push([
    //         "share" => $irs_share,
    //         "name" => $irs->name,
    //         "account_number" => $irs->account_number,
    //         "bank_code" => $irs->bank_code,
    //         "currency" => $irs->currency,
    //         "recipient_code" => $irs->recipient_code,
    //     ]);


    //     $subaccounts->push([
    //         "share" => $ubt_share,
    //         "name" => $ubt->name,
    //         "account_number" => $ubt->account_number,
    //         "bank_code" => $ubt->bank_code,
    //         "currency" => $ubt->currency,
    //         "recipient_code" => $ubt->recipient_code,
    //     ]);
        
    //     return $subaccounts;
    // }
    // private static function prepareSubaccountsForSplit($invoice, $invoiceAmount, $gateway){
         
    //     $issuer = $invoice->issuer;
        
    //     if(get_class($issuer) == CorporateTaxPayer::class){
    //         if(!empty($invoice->issuedThrough)){
    //             $issuer = get_class($invoice->issuedThrough) == Agent::class?$invoice->issuedThrough->vendor:$invoice->issuedThrough;
    //         }else{
    //             $issuer = null;
    //         }
    //     }else if(get_class($issuer) == Agent::class){
    //         $issuer = $issuer->vendor;
    //     }else if(get_class($issuer) == Vendor::class){
    //         $issuer = $issuer;
    //     }else{
    //         $issuer = null;
    //     }

    //     $revenueSubHead = $invoice->invoiceType->revenueSubHead;
    

    //     $beneficiaries = Beneficiary::query();
    //     if(!empty($issuer)){
    //         if(in_array(get_class($issuer), [Vendor::class])){
    //             $beneficiaries = $beneficiaries->where(function ($query) use ($issuer) {
    //                 $query->where('beneficiary_type', get_class($issuer))
    //                     ->where('beneficiary_id', $issuer->id);
    //             })->where('status', Status::ACTIVE);
    //         }
    //     }

    //     if($invoice->revenue_source_type == Lga::class){
    //         $beneficiaries = $beneficiaries->orWhere(function ($query) use ($invoice) {
    //             $query->where('beneficiary_type', $invoice->revenue_source_type)
    //                 ->where('beneficiary_id', $invoice->revenue_source_id);
    //         })->where('status', Status::ACTIVE);
    //     }

    //     if($invoice->revenue_source_type == RevenueSource::class){
    //         $beneficiaries = $beneficiaries->orWhere(function ($query) use ($invoice) {
    //             $query->where('beneficiary_type', $invoice->revenue_source_type)
    //                 ->where('beneficiary_id', $invoice->revenue_source_id);
    //         })->where('status', Status::ACTIVE);
    //     }

    //     $options = array_keys(Beneficiary::defaultGatewayOptions());

    //     if(in_array($gateway, $options)){
    //         $beneficiaries = $beneficiaries->where('options->'.$gateway, true)->get();
    //     }else{
    //         $beneficiaries = collect(); //just to return empty;
    //     }
         
    //     $subaccounts = $beneficiaries->map(function ($beneficiary) use ($revenueSubHead, $invoiceAmount) {
            
    //         $share = 0;
    //         switch ($beneficiary->beneficiary_type) {
    //             case LGA::class:
    //                 $share = $revenueSubHead->mda_share;
    //                 break;
    //             case Vendor::class:
    //                 $share = $revenueSubHead->vendor_share;
    //                 break;
    //             case RevenueSource::class:
    //                 $share = $revenueSubHead->vendor_share;
    //                 break;
    //         }

    //         if ($revenueSubHead->share_type === 'fixed') {
    //             $share = ($share / $invoiceAmount) * 100;
    //         }
          
    //         return [
    //             "id" => $beneficiary->id,
    //             "share" => $share,
    //             "name" => $beneficiary->name,
    //             "amount" =>($share/100) * $invoiceAmount,
    //             "account_name" => $beneficiary->account_name,
    //             "account_number" => $beneficiary->account_number,
    //             "bank_code" => $beneficiary->bank_code,
    //             "currency" => $beneficiary->currency,
    //             "recipient_code" => $beneficiary->recipient_code,
    //             "status" => $beneficiary->status,
    //             "subaccount_code" => $beneficiary->subaccount_code,
    //             "deductFeeFrom"=>0
    //         ];
    //     });
    //     $totalShare = $subaccounts->sum('share');
        
        
    //     $beneficiaries = Beneficiary::whereIn('name', ['IRS', 'UBT'])->get();
    //     $ubt = $beneficiaries->where('name', 'UBT')->where('status', true)->first();
    //     $irs = $beneficiaries->where('name', 'IRS')->first();
    //     if(empty($irs)){
    //         abort(422, 'IGR Account not found');
    //     }
    //     $ubt_share = $ubt?->share ??0;
        
    //     //if($revenueSubHead->irs_share){  
    //         $irs_share = $revenueSubHead->irs_share;

    //         if ($revenueSubHead->share_type === 'fixed') {
    //             $mda_vendor = $revenueSubHead->mda_share + $revenueSubHead->vendor_share;
    //             $mda_vendor_perc = ($mda_vendor / $invoiceAmount) * 100;
    
    //             if ((100 - $mda_vendor_perc) < $ubt_share) {   
    //                 $difference = 100 - ($totalShare + $ubt_share);
    //                 $irs_share = $difference;
    //                 $ubt_share = $ubt_share;
    //             }
    
    //         } else {
    //             if ((100 - $totalShare) < $ubt_share) {
    //                 $difference = 100 - ($totalShare + $ubt_share);
    //                 $irs_share = $difference;
    //                 $ubt_share = $ubt_share;
    //             }
    //         }
            
    //     // }else{
    //     //     $subaccounts = collect();
    //     //     $irs_share = 100;
    //     // }
        
    //     if($ubt_share != 0){
    //         if(empty($ubt)){
    //             abort(422, 'An Account not found');
    //         }
    //         $subaccounts->push([
    //             "id" => $ubt->id,
    //             "share" => $ubt_share,
    //             "amount" =>($ubt_share/100) * $invoiceAmount,
    //             "name" => $ubt->name,
    //             "account_number" => $ubt->account_number,
    //             "account_name" => $ubt->account_name,
    //             "bank_code" => $ubt->bank_code,
    //             "currency" => $ubt->currency,
    //             "recipient_code" => $ubt->recipient_code,
    //             "subaccount_code" => $ubt->subaccount_code,
    //             "status"=>$ubt->status,
    //             "deductFeeFrom"=>0
    //         ]);
    //     }
        
    //     $irsArraySubaccount =[
    //         "id" => $irs->id,
    //         "share" => $irs_share,
    //         "amount"=> ($irs_share/100) * $invoiceAmount,
    //         "name" => $irs->name,
    //         "account_number" => $irs->account_number,
    //         "account_name" => $irs->account_name,
    //         "bank_code" => $irs->bank_code,
    //         "currency" => $irs->currency,
    //         "recipient_code" => $irs->recipient_code,
    //         "subaccount_code" => $irs->subaccount_code,
    //         "status" => $irs->status,
    //         "deductFeeFrom"=>1
    //     ];

    //     if($irs_share > 0){
    //         $subaccounts->push($irsArraySubaccount);
    //     }else if($subaccounts->isEmpty()){
    //         $subaccounts->push([...$irsArraySubaccount, "amount"=>$invoiceAmount ]);
    //     }
        
        
    //     return $subaccounts;
    // }
    // private static function prepareSubaccountsForSplit($invoice, $invoiceAmount, $gateway)
    // {
    //     $issuer = $invoice->issuer;

    //     if (!empty($invoice->issuedThrough)) {
    //         $issuer = get_class($invoice->issuedThrough) == Agent::class ? $invoice->issuedThrough->vendor : $invoice->issuedThrough;
    //     } elseif (get_class($issuer) == Agent::class) {
    //         $issuer = $issuer->vendor;
    //     } elseif (get_class($issuer) == Vendor::class) {
    //         $issuer = $issuer;
    //     } else {
    //         $issuer = null;
    //     }
    
    //     $revenueSubHead = $invoice->invoiceType->revenueSubHead;

    //     $irs = Beneficiary::where('name', 'IRS')->first();
    //     // Case 1: All shares are 0, IRS should get 100%
    //     $irsSubaccountFull =[
    //         "id" => $irs->id,
    //         "share" => 100,
    //         "amount" => round($invoiceAmount, 2),
    //         "name" => $irs->name,
    //         "account_number" => $irs->account_number,
    //         "account_name" => $irs->account_name,
    //         "bank_code" => $irs->bank_code,
    //         "currency" => $irs->currency,
    //         "recipient_code" => $irs->recipient_code,
    //         "subaccount_code" => $irs->subaccount_code,
    //         "status" => $irs->status,
    //         "deductFeeFrom" => 1
    //     ];

    //     if ($revenueSubHead->mda_share == 0 && $revenueSubHead->vendor_share == 0 && $revenueSubHead->irs_share == 0) {
    //         $revenueSubHead->share_type ='percentage';
    //         $revenueSubHead->save();
    //         return collect([$irsSubaccountFull]);
    //     }else if($revenueSubHead->irs_share == 100){
    //         $revenueSubHead->share_type ='percentage';
    //         $revenueSubHead->save();
    //         return collect([$irsSubaccountFull]);
    //     }
     
    //     // Vendor is NULL, add vendor share to IRS
    //     if ($issuer === null) {
    //         $revenueSubHead->irs_share += $revenueSubHead->vendor_share;
    //         $revenueSubHead->vendor_share = 0;
    //     }
        
    //     $beneficiaries = Beneficiary::query();
    
    //     if (!empty($issuer) && in_array(get_class($issuer), [Vendor::class])) {
    //         $beneficiaries = $beneficiaries->where(function ($query) use ($issuer) {
    //             $query->where('beneficiary_type', get_class($issuer))
    //                 ->where('beneficiary_id', $issuer->id);
    //         })->where('status', Status::ACTIVE);
    //     }
    
    //     if ($invoice->revenue_source_type == Lga::class || $invoice->revenue_source_type == RevenueSource::class) {
    //         $beneficiaries = $beneficiaries->orWhere(function ($query) use ($invoice) {
    //             $query->where('beneficiary_type', $invoice->revenue_source_type)
    //                 ->where('beneficiary_id', $invoice->revenue_source_id);
    //         })->where('status', Status::ACTIVE);
    //     }
    
    //     $options = array_keys(Beneficiary::defaultGatewayOptions());
    
    //     if (in_array($gateway, $options)) {
    //         $beneficiaries = $beneficiaries->where('options->' . $gateway, true)->get();
    //     } else {
    //         return collect();
    //     }
    
    //     $subaccounts = $beneficiaries->map(function ($beneficiary) use ($revenueSubHead, $invoiceAmount) {
    //         $share = 0;
    //         switch ($beneficiary->beneficiary_type) {
    //             case LGA::class:
    //                 $share = $revenueSubHead->mda_share;
    //                 break;
    //             case Vendor::class:
    //                 $share = $revenueSubHead->vendor_share;
    //                 break;
    //             case RevenueSource::class:
    //                 $share = $revenueSubHead->mda_share;
    //                 break;
    //         }
    
    //         if ($revenueSubHead->share_type === 'fixed') {
    //             $share = round(($share / $invoiceAmount) * 100, 2);
    //         }
    
    //         return [
    //             "id" => $beneficiary->id,
    //             "share" => $share,
    //             "name" => $beneficiary->name,
    //             "amount" => round(($share / 100) * $invoiceAmount, 2),
    //             "account_name" => $beneficiary->account_name,
    //             "account_number" => $beneficiary->account_number,
    //             "bank_code" => $beneficiary->bank_code,
    //             "currency" => $beneficiary->currency,
    //             "recipient_code" => $beneficiary->recipient_code,
    //             "status" => $beneficiary->status,
    //             "subaccount_code" => $beneficiary->subaccount_code,
    //             "deductFeeFrom" => 0
    //         ];
    //     });

    //     $totalShare = round($subaccounts->sum('share'), 2);
    
    //     $ubt = Beneficiary::where('name',  'UBT')->where('status', true)->first();

    //     $ubt_share = round($ubt?->share ?? 0, 2);
    //     $irs_share = round($revenueSubHead->irs_share ?? 0, 2);

    //     $remainingShare = 100 - ($totalShare + $ubt_share+$irs_share );
    //     if ($remainingShare > 0) {
    //         $irs_share += $remainingShare;
    //     }
    
    //     if ($revenueSubHead->share_type === 'fixed') {
    //         $mda_vendor = $revenueSubHead->mda_share + $revenueSubHead->vendor_share;
    //         $mda_vendor_perc = round(($mda_vendor / $invoiceAmount) * 100, 2);
    
    //         if ((100 - $mda_vendor_perc) < $ubt_share) {
    //             $difference = 100 - ($totalShare + $ubt_share);
    //             $irs_share = round($difference, 2);
    //         }
    //     } else {
    //         if ((100 - $totalShare) < $ubt_share) {
    //             $difference = 100 - ($totalShare + $ubt_share);
    //             $irs_share = round($difference, 2);
    //         }
    //     }
    
    //     if ($ubt_share != 0) {
    //         if (empty($ubt)) {
    //             abort(422, 'An Account not found');
    //         }
    //         $subaccounts->push([
    //             "id" => $ubt->id,
    //             "share" => $ubt_share,
    //             "amount" => round(($ubt_share / 100) * $invoiceAmount, 2),
    //             "name" => $ubt->name,
    //             "account_number" => $ubt->account_number,
    //             "account_name" => $ubt->account_name,
    //             "bank_code" => $ubt->bank_code,
    //             "currency" => $ubt->currency,
    //             "recipient_code" => $ubt->recipient_code,
    //             "subaccount_code" => $ubt->subaccount_code,
    //             "status" => $ubt->status,
    //             "deductFeeFrom" => 0
    //         ]);
    //     }
    
    //     $irsArraySubaccount = [
    //         "id" => $irs->id,
    //         "share" => $irs_share,
    //         "amount" => round(($irs_share / 100) * $invoiceAmount, 2),
    //         "name" => $irs->name,
    //         "account_number" => $irs->account_number,
    //         "account_name" => $irs->account_name,
    //         "bank_code" => $irs->bank_code,
    //         "currency" => $irs->currency,
    //         "recipient_code" => $irs->recipient_code,
    //         "subaccount_code" => $irs->subaccount_code,
    //         "status" => $irs->status,
    //         "deductFeeFrom" => 1
    //     ];
    
    //     if ($irs_share > 0) {
    //         $subaccounts->push($irsArraySubaccount);
    //     } 
        
    //     return $subaccounts;
    // }
    
    private static function prepareSubaccountsForSplit($invoice, $invoiceAmount, $gateway)
    {
        $issuer = $invoice->issuer;

        if (!empty($invoice->issuedThrough) && !($invoice->issuedThrough instanceof Staffer)) {
            $issuer = $invoice->issuedThrough instanceof Agent ? $invoice->issuedThrough->vendor : $invoice->issuedThrough;
        } elseif ($issuer instanceof Agent) {
            $issuer = $issuer->vendor;
        } elseif (!$issuer instanceof Vendor) {
            $issuer = null;
        }

        $revenueSubHead = $invoice->invoiceType->revenueSubHead;
        $irs = Beneficiary::where('name', 'IRS')->first();
        if (!$irs) {
            abort(422, "IRS Beneficiary not found");
        }

        $irsSubaccountFull = [
            "id" => $irs->id,
            "share" => 100,
            "amount" => $invoiceAmount, 
            "name" => $irs->name,
            "account_number" => $irs->account_number,
            "account_name" => $irs->account_name,
            "bank_code" => $irs->bank_code,
            "currency" => $irs->currency,
            "recipient_code" => $irs->recipient_code,
            "subaccount_code" => $irs->gateways[$gateway]['code'] ?? $irs->subaccount_code,
            "status" => $irs->status,
            "deductFeeFrom" => 1,
        ];
        
        if ($revenueSubHead->mda_share == 0 && $revenueSubHead->vendor_share == 0 && $revenueSubHead->irs_share == 0) {
            $revenueSubHead->update(['share_type' => 'percentage']);
            return collect([$irsSubaccountFull]);
        } elseif ($revenueSubHead->irs_share == 100) {
            $revenueSubHead->update(['share_type' => 'percentage']);
            return collect([$irsSubaccountFull]);
        }
        
        $beneficiaries = Beneficiary::query();
        $queryExists = false;
        if ($issuer == null) {
            $revenueSubHead = clone $revenueSubHead; // Prevent modifying the original
            $revenueSubHead->irs_share += $revenueSubHead->vendor_share;
            $revenueSubHead->vendor_share = 0;
        }
        else{
            if ($issuer instanceof Vendor &&  $revenueSubHead->vendor_share > 0) {
                $beneficiaries->where('beneficiary_type', Vendor::class)
                            ->where('beneficiary_id', $issuer->id)
                            ->where('status', Status::ACTIVE);
                $queryExists = true;
            }
        }

        
        if (in_array($invoice->revenue_source_type, [Lga::class, RevenueSource::class]) && $revenueSubHead->mda_share > 0 ) {
            $beneficiaries->orWhere('beneficiary_type', $invoice->revenue_source_type)
                        ->where('beneficiary_id', $invoice->revenue_source_id)
                        ->where('status', Status::ACTIVE);
            $queryExists = true;
        }


        if (in_array($gateway, array_keys(Beneficiary::defaultGatewayOptions()))) {
            $beneficiaries->whereJsonContains('options->' . $gateway, true);
        }
        else {
            return collect([$irsSubaccountFull]);
        }
        
        
        if($queryExists){
            $beneficiaries = $beneficiaries->get();
        }
        else{
            return collect([$irsSubaccountFull]);
        }


        if($revenueSubHead->irs_share >0){
            $beneficiaries = $beneficiaries->merge(collect([$irs]));
        }

        $subaccounts = $beneficiaries->map(function ($beneficiary) use ($revenueSubHead, $invoiceAmount, $gateway) {
            $share = match (true) {
                in_array($beneficiary->beneficiary_type, [LGA::class, RevenueSource::class]) => $revenueSubHead->mda_share,
                $beneficiary->beneficiary_type === Vendor::class => $revenueSubHead->vendor_share,
                $beneficiary->beneficiary_type === null && $beneficiary->name === 'IRS' => $revenueSubHead->irs_share,
                default => 0
            };
            
            if ($revenueSubHead->share_type === 'fixed') {
                $share = ($share / $invoiceAmount) * 100; 
            }

            $amount = ($share / 100) * $invoiceAmount;
            $amount = round($amount, 2);
            return [
                "id" => $beneficiary->id,
                "share" => round($share, 2),
                //"amount" => floor(($share / 100) * $invoiceAmount),
                "amount" => $amount, //fix precision issue
                "name" => $beneficiary->name,
                "account_number" => $beneficiary->account_number,
                "account_name" => $beneficiary->account_name,
                "bank_code" => $beneficiary->bank_code,
                "currency" => $beneficiary->currency,
                "recipient_code" => $beneficiary->recipient_code,
                "subaccount_code" => $beneficiary->gateways[$gateway]['code'] ?? $beneficiary->subaccount_code,
                "status" => $beneficiary->status,
                "deductFeeFrom" => 0
            ];
        });
            
        
        if ($subaccounts->isNotEmpty()) {
            $subaccounts = $subaccounts->map(function ($subaccount, $index) use ($subaccounts) {
                if ($index === $subaccounts->count() - 1) {
                    $subaccount['deductFeeFrom'] = 1;
                }
                return $subaccount;
            });

            $sum = $subaccounts->sum('amount');
            $diff = round($invoiceAmount - $sum, 2);
     //       Log::info("diff : " . $diff);
            if ($diff != 0) {
                $subaccounts = $subaccounts->map(function ($item, $index) use ($diff, $subaccounts) {
                    if ($item['deductFeeFrom'] === 1) {
                        $item['amount'] = round($item['amount'] + $diff, 2);
                    }
                    return $item;
                });
            }

        }

        return $subaccounts;
    }

    private static function splitPayments($invoice, $amountToPay,$gateway){

        $allSubaccounts = collect();
        $amountLeft = $amountToPay;


        $invoiceAmount = $invoice->amount;
        
        
        if($amountLeft < $invoiceAmount){
            $invoiceAmount  = $amountLeft;
        }


        $subaccounts = self::prepareSubaccountsForSplit($invoice, $invoiceAmount, $gateway);

        $amountLeft -= $invoiceAmount;
        $allSubaccounts = $allSubaccounts->merge($subaccounts);
        
    
        
       
        $totalAmount = $allSubaccounts->sum('amount');
        $distinctAccounts = [];
        foreach ($allSubaccounts as $subaccount) {
            if(empty($subaccount['bank_code'])){
                abort(422, 'Bank code is required');
            }
    
            if(empty($subaccount['account_number'])){
                abort(422, 'Account Number is required');
            }
            

            if (isset($distinctAccounts[$subaccount['name']])) {
                $distinctAccounts[$subaccount['name']]['amount'] += $subaccount['amount'];
            } else {
                $distinctAccounts[$subaccount['name']] = $subaccount;
            }
        }

        // if(count($distinctAccounts) == 1 && $distinctAccounts["IRS"]){
        //     $distinctAccounts["IRS"]["amount"] = $amountToPay;
        //     $distinctAccounts["IRS"]["share"] = 100;
        //     $totalAmount = $amountToPay;
        // }



        
        $ac = collect($distinctAccounts)->map(function ($subaccount) use ($totalAmount) {
            return [
                "id" => $subaccount['id'],
                "recipient_code" => $subaccount['recipient_code'],
                "subaccount_code" => $subaccount['subaccount_code']??null,
                "share" => ($subaccount['amount'] / $totalAmount) * 100,
                "amount" => $subaccount['amount'],
                "account_name" => $subaccount['account_name'],
                "account_number" => $subaccount['account_number'],
                "bank_code" => $subaccount['bank_code'],
                "currency" => $subaccount['currency'],
                "status" => $subaccount['status'],
                "deductFeeFrom"=>$subaccount['deductFeeFrom']
            ];
        })->values();
        return $ac;
    }
    
    public static function isSoleProrietor($businessStructureId){
        return BusinessStructure::where('name','Like', '%proprietor%')
                    ->where('id', $businessStructureId)
                    ->exists();
    
    }

    public static function getUserFromRequest($request){

        $token = $request->bearerToken();
        $user = null;
        
        if ($token &&  strlen($token) > 20) {
            //check if valid token

                // Find the token in the database
            // Find the token in the database
            $parser = app(\Lcobucci\JWT\Parser::class);
            $parsedToken = $parser->parse($token);
            
            // Extract the token ID (jti claim)
            $tokenId = $parsedToken->claims()->get('jti');
 
            // Find the token in the database using the token ID
            $tokenModel = Token::where('id', $tokenId)->first();
 
            if ($tokenModel && !$tokenModel->revoked) {
                 if(in_array('api-staff',$tokenModel->scopes)){
                     $user = Staffer::find($tokenModel->user_id);
                 }else if(in_array('api-vendor',$tokenModel->scopes)){
                     $user = Vendor::find($tokenModel->user_id);
                 }else if(in_array('api-agent',$tokenModel->scopes)){
                    $user = Agent::find($tokenModel->user_id);
                }else if(in_array('api-individual',$tokenModel->scopes)){
                     $user = IndividualTaxPayer::find($tokenModel->user_id);
                 }else if(in_array('api-corporate',$tokenModel->scopes)){
                     $user = CorporateTaxPayer::find($tokenModel->user_id);
                 }else{
                     $user = null;
                 }
            }
            return $user;
        }
 
    }

    
}