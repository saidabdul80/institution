<?php

use App\Enums\RevenueType;
use App\Enums\VaultType;
use App\Http\Resources\PaymentResource;
use App\Models\Agent;
use App\Models\CorporateTaxPayer;
use App\Models\CorporateTaxPayerEmployee;
use App\Models\IndividualTaxPayer;
use App\Models\Lga;
use App\Models\RevenueSource;
use App\Models\Staffer;
use NumberToWords\NumberToWords;
use App\Models\TaxableBusinessAsset;
use App\Models\Ticket;
use App\Models\Vendor;
use App\Services\Util;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// function authorize($user, $permission, $req = null, $throwError = true): bool
// {

//     $isAuthorized = false;

//     switch ($permission) {
//         case CAN_ENROLL:
//             $isAuthorized = Util::canPerformEnrollment($user, $req);
//             break;
//         case CAN_COLLECT_TAX:
//             $isAuthorized = Util::canCollectPayment($user, $req);
//             break;
//         default:
//             $isAuthorized = $user ? $user->hasPermissionTo($permission) : false;
//             break;
//     }

//     if ($isAuthorized) {
//         return true;
//     }
//     if ($throwError) {
//         abort(403, "Permission Denied");
//     }
//     return false;
// }

function authorize($user, $permission, $req = null, $throwError = true): bool
{
    $isAuthorized = false;

    if (is_array($permission)) {
        foreach ($permission as $perm) {
            if (checkPermission($user, $perm, $req)) {
                $isAuthorized = true;
                break;
            }
        }
    } else {
        $isAuthorized = checkPermission($user, $permission, $req);
    }

    if ($isAuthorized) {
        return true;
    }
    
    if ($throwError) {
        abort(403, "Permission Denied");
    }
    
    return false;
}

function checkPermission($user, $permission, $req = null): bool
{
    switch ($permission) {
        case CAN_ENROLL:
            return Util::canPerformEnrollment($user, $req);
        case CAN_COLLECT_TAX:
            return Util::canCollectPayment($user, $req);
        default:
            return $user ? $user->hasPermissionTo($permission) : false;
    }
}

function getLgaIdIfHasPermission($user): ?int
{
    $stateId = Util::getConfigValue('state_id');
    $lgas = Lga::where('state_id', $stateId)->pluck('id', 'name');

    foreach ($lgas as $name => $id) {
        if ($user->hasPermissionTo($name)) {
            return $id;
        }
    }

    return null;
}



/**
 * Get the date range from the request.
 *
 * This function retrieves the date range from the given request.
 * If the date is provided as an array, it parses and formats the start and end dates.
 * If the date is provided as a single value, it returns the formatted date.
 * If no date is provided, it defaults to the start and end dates of the current year.
 *
 * @param \Illuminate\Http\Request $request The request object containing the date information.
 * @return array|string An array with the start and end dates or a single formatted date string.
 */
function get_date_range($request)
{

    if ($request->date) {
        if(is_array($request->date)) {
            return $request->date;
        }else{
            return explode(',', $request->date);
        }
    }

    $endDate = $request->end_date;
    $startDate = $request->start_date;
    $date = [];
    if ($startDate) {
        $date[] = Carbon::parse($startDate)->format('Y-m-d');
    }

    if ($endDate) {
        $date[] = Carbon::parse($endDate)->format('Y-m-d');
    }

    if (empty($startDate) && empty($endDate)) {
        $date = [
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];
    }

    return $date;
}

function get_date_range_prev($dateRange)
{
    $startDate = Carbon::parse($dateRange[0]);
    $endDate = Carbon::parse($dateRange[1]);

    $diffInMonths = $startDate->diffInMonths($endDate) + 1;

    $prevStartDate = $startDate->subMonths($diffInMonths);
    $prevEndDate = $endDate->subMonths($diffInMonths);

    return  [
        $prevStartDate->format('Y-m-d'),
        $prevEndDate->format('Y-m-d')
    ];
}


function this_month($request)
{
    return $request->date ?? [
        Carbon::now()->startOfMonth()->format('Y-m-d'),
        Carbon::now()->endOfMonth()->format('Y-m-d'),
    ];
}

function get_date_range_from($from)
{
    $registeredYear = Carbon::parse($from)->year;

    $currentYear = Carbon::now()->year;
    $yearsRange = range($registeredYear, $currentYear);
    $yearsData = [];
    foreach ($yearsRange as $year) {
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear()->format('Y-m-d');
        $yearsData[] = [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
    return $yearsData;
}


function validate_phone_number($phoneNumber)
{
    // Remove any non-numeric characters
    $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

    // Check if the number starts with '0' and remove it
    if (substr($phoneNumber, 0, 1) === '0') {
        $phoneNumber = substr($phoneNumber, 1); // Remove the leading '0'
    }

    // Add '234' at the beginning if not already present
    if (substr($phoneNumber, 0, 3) !== '234') {
        $phoneNumber = '234' . $phoneNumber;
    }

    // Check if the modified number starts with '234'
    if (substr($phoneNumber, 0, 3) === '234') {
        return $phoneNumber;
    } else {
        return null;
    }
}


function generate_random_number($length = 6)
{
    return str_pad(mt_rand(0, 999999), $length, '0', STR_PAD_LEFT);
}

function expires_at($t = 30)
{
    return Carbon::now()->addMinutes($t)->format("Y-m-d H:i:s");
}

function get_user_type($user_type)
{
    $userType = '';
    switch ($user_type) {
        case 'individualTaxPayer':
            $userType = "individual";
            break;
        case 'corporateTaxPayer':
            $userType = "corporate";
            break;
        default:
            $userType = $user_type;
            break;
    }
    return $userType;
}

function get_related_class($related_type)
{
    if (class_exists($related_type)) {
        return $related_type;
    }
    
    $class = NULL;
    switch ($related_type) {
        case 'assets':
            $class = TaxableBusinessAsset::class;
            break;
        case 'paye':
            $class = CorporateTaxPayerEmployee::class;
            break;
        case 'ticket':
            $class = Ticket::class;
            break;
        default:
            break;
    }
    return $class;
}


function number_to_word($number)
{

    $numberToWords = new NumberToWords();
    $numberTransformer = $numberToWords->getNumberTransformer('en');
    $words = $numberTransformer->toWords($number);

    return $words;
}

function getBase64Image($path)
{
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        try {
            $response = Http::get($path);

            if ($response->failed()) {
            //    Log::info("Failed to fetch remote image, HTTP status code: " . $response->status());
                return '';
            }

            $imageData = $response->body();
            $mimeType = $response->header('Content-Type');
            $base64 = base64_encode($imageData);
            return 'data:' . $mimeType . ';base64,' . $base64;
        } catch (\Exception $e) {
            Log::info("Error fetching remote image: " . $e->getMessage());
            return '';
        }
    }

    $fullPath = public_path($path);

    if (!is_file($fullPath)) {
        Log::info("File not found: " . $fullPath);
        return '';
    }

    try {
        $imageData = file_get_contents($fullPath);
        $base64 = base64_encode($imageData);
        $mimeType = mime_content_type($fullPath);
        return 'data:' . $mimeType . ';base64,' . $base64;
    } catch (\Exception $e) {
        Log::info("Error processing image: " . $e->getMessage());
        return '';
    }
}

function get_remote_mime_type($url)
{
    // Get the MIME type of a remote image
    $headers = get_headers($url, 1);
    return $headers['Content-Type'];
}

function maskPhoneNumber($phoneNumber) {
    $length = strlen($phoneNumber);

    if ($length <= 6) {
        return $phoneNumber; // Not enough digits to mask
    }

    $start = substr($phoneNumber, 0, 3);
    $end = substr($phoneNumber, -3);

    // Calculate the number of asterisks needed
    $maskedSection = str_repeat('*', $length - 6);

    return $start . $maskedSection . $end;
}

function toTitleCase($str) {
    return implode(' ', array_map(function ($word) {
        return strtoupper(substr($word, 0, 1)) . substr($word, 1);
    }, explode(' ', $str)));
}

function extractVariables($template){
    preg_match_all('/{(\w+)}/', $template, $matches);
    $response = [];
    foreach ($matches[1] as $key) {
        $response[$key] = 0;
    }
    if(empty($response)){
        return (object)[];
    }
    return $response;
}

function get_user_class($user_type)
{
    $userClass = '';
    switch (strtolower($user_type)) {
        case 'staff':
            $userClass = Staffer::class;
            break;
        case 'vendor':
            $userClass = Vendor::class;
            break;
        case 'agent':
            $userClass = Agent::class;
            break;
        case 'corporate':
            $userClass = CorporateTaxPayer::class;
            break;
        case 'individual':
            $userClass = IndividualTaxPayer::class;
            break;
        case 'corporatetaxpayer':
            $userClass = CorporateTaxPayer::class;
            break;
        case 'individualtaxpayer':
            $userClass = IndividualTaxPayer::class;
            break;
        default:
            $userClass = $user_type;
            break;
    }
    return $userClass;
}

function isValidEmail($email) {
    return preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email);
}

function get_date_range_interval_type($dateRange) {
    // Ensure that $dateRange has exactly two dates
    if (count($dateRange) !== 2) {
        throw new InvalidArgumentException("Date range must contain exactly two dates.");
    }

    $start = Carbon::parse($dateRange[0]);
    $end = Carbon::parse($dateRange[1]);
    $days = $start->diffInDays($end);

    // Determine the interval type based on the number of days
    if ($days >= 365) {
        return 1; // Yearly
    } elseif ($days >= 90) {
        return 4; // Quarterly (approximately 90 days)
    } elseif ($days >= 30) {
        return 12; // Monthly (approximately 30 days)
    } else {
        return 365; // Daily
    }
}
function getAppMda($request)
{
    $result = [
        "filters"=>[],
        "client"=>null
    ];
    $filters = $result['filters'];
    $app = null;
    $appOwner = null;

    // Check if the 'x-client-id' header exists
    if ($request->hasHeader('x-client-id')) {
        // Fetch the app based on the client ID
        $app = DB::table('oauth_clients')->where('id', $request->header('x-client-id'))->first();
        if (!$app) {
            return $result;
        }
        // Resolve the provider's model from the config
       $model = get_user_class($app->provider);

        if (empty($model)) {
           return $result;
        }

        // Fetch the app owner using the resolved model
        $appOwner = $model::find($app->user_id);
    }

    // Apply filters if the app owner is a Staffer
    if ($appOwner instanceof Staffer) {
        $filters['revenue_source_type'] = $appOwner->mda_type;
        $filters['revenue_source_id'] = $appOwner->mda_id;
        $filters['withoutPaginate'] = 1;
    }

    // Return filters and client details
    return collect([
        'filters' => $filters,
        'client' => $appOwner,
        'redirect_url'=> $app?->redirect,
    ]);
}

function getMdaRedirect($payment){
   
    // Check if the 'x-client-id' header exists

        // Fetch the app based on the client ID
    //$paymentr = new  PaymentResource($payment);
    //$invoice =  $paymentr->tax_invoice ?? $paymentr->tax_invoices[0];
    //$user_type = get_user_type($payment->owner->user_type);
    //'provider'=>$invoice->issuer_type, 
    $app = DB::table('oauth_clients')->where(['id'=> $payment?->client_id])->first();
    if (!$app) {
        return null;
    }
    return $app->redirect;
    
}


function prepareError($message){
    return [
        "errors"=>[
           "message"=>$message
        ]
    ];  
}


function getFilters($request){
    $user = $request->user();
    $baseFilter = [];
    if($user::class !== Staffer::class){
        return $baseFilter;
    }
    
    if( str_contains(strtoupper($user->category),'MDA')){
        $baseFilter['revenue_source_type'] = Util::getMDASoureModel($user->mda_type);
        $baseFilter['revenue_source_id'] = $user->mda_id;
    }
    if(authorize($user, CAN_ACCESS_ONLY_ISSUED_INVOICES,null, false)){
        $baseFilter['issuer_type'] = $user::class;
        $baseFilter['issuer_id'] = $user->id;
    }
    return $baseFilter;
}


  function getVault($invoices)
    {
        // Define vault mapping rules (extendable)
        $vaultMappings = [
            RevenueType::WITHOLDING_TAX => VaultType::WALLET,
            //new can be added here
        ];

        // Define types that must not allow mixed revenue types
        $notMultiInvoice = [
            RevenueType::WITHOLDING_TAX,
            RevenueType::PAYE,
        ];

       // Get unique revenue types from invoices
        $existTypesInInvoices = $invoices->map(fn($invoice) => $invoice->invoiceType->revenueSubHead->revenue_type)->unique();

        // Check if any restricted type is mixed with another revenue type
        $intersectedTypes = $existTypesInInvoices->intersect($notMultiInvoice);

        if ($intersectedTypes->isNotEmpty() && $existTypesInInvoices->count() > 1) {
            abort(400, 'You cannot create a multi-invoice along with ' . RevenueType::getKey($intersectedTypes->first()));
        }

        // Determine the vault type based on mapping
        $vaultType = $existTypesInInvoices->count() === 1 && isset($vaultMappings[$existTypesInInvoices->first()])
            ? $vaultMappings[$existTypesInInvoices->first()]
            : VaultType::MAIN;

        return $vaultType;
    }