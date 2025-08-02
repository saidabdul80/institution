<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentGateway\GatewayFactory;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentController extends Controller
{
    protected $paymentService;

    protected $gatewayFactory;
    
    public function __construct(PaymentService $paymentService)
    {
        $this->gatewayFactory = new GatewayFactory;
        $this->paymentService = $paymentService;
    }

    public function show($reference)
    {
        $payment = $this->paymentService->getPaymentByReference($reference);
        return new ApiResource($payment,  false, 200);
    }


    public function requery(Request $request)
    {
        try {
            $requery = $this->paymentService->requery($request->reference);
            return new ApiResource($requery, false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
        }
    }

    public function paymentWebhook(Request $request)
    {
        try {
            return $this->paymentService->paymentWebhook($request->all());
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function pay(Request $request){
        try {
            $request->validate([
                "invoice_id" => "required",
            ]);
            $response = $this->paymentService->pay($request->get('invoice_id'));
            return new ApiResource($response, false, 200 );
        }catch(ValidationException $e){
            return new ApiResource(array_values($e->errors())[0], true, 400 );
        } catch (Exception $e) {
            return $e;
            return new ApiResource($e->getMessage(), true, $e->getCode());
        }
    }

     public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required',
            'gateway' => 'required|string',
            'reference' => 'nullable|string',
        ]);
        
        $user = $request->user();
        $invoice_id = $request->invoice_id;
        
        $invoice = Invoice::where('id', $invoice_id)->first();
        $owner_type = $invoice->owner_type;
        $owner_id = $invoice->owner_id;
      

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }
        
        $gateway = $request->gateway;
        return $this->beginPaymentProcess(
            $invoice_id, 
            $gateway, 
            $owner_type, 
            $owner_id, 
            null, 
            $request->rrr, 
        );
    }


      public function beginPaymentProcess($invoice_id, $gateway, $owner_type, $owner_id, $wallet = null, $rrr = null)
    {
        

        $invoice = Invoice::where('id', $invoice_id)->first();
        
        
        if (!$invoice) {
            return response()->json(["errors" => "No valid invoice found"], 422);
        }
        

        try {
            $paid =  $invoice->status == 'paid';

            if($paid) {
                throw new \Exception('Payment has already been completed on this invoice', 422);
            }
    
            DB::beginTransaction();
            
            $internalReference = Paystack::genTranxRef();
            $gatewayService = $this->gatewayFactory->create($gateway);
            // Use total amount (amount + charges) for payment
            $totalAmount = $invoice->total_amount;

            $paymentData  = $gatewayService->preparePaymentData(
                $gateway, $totalAmount, $owner_type, $owner_id, null, $invoice, $internalReference, null, $rrr
            );
           Log::info("paymentData2 : " . json_encode($paymentData));
            $payment = Payment::create($paymentData);
            $response = $this->handleGatewayPaymentRecord(
                        $gateway,
                        $invoice,
                        $payment,
                        $paymentData,
                        $totalAmount,
                        $wallet,
                        null
                    );
            DB::commit();

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        } catch(\Throwable  $e){
            Log::error($e);
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        } catch(\TypeError  $e){    
            Log::error($e);    
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function handleWebhook(Request $request)
    {
        $gateway = $request->gateway;
        
        try {
            $gatewayService = $this->gatewayFactory->create($gateway);
            return $gatewayService->handleWebhook($request);
        } catch (\Exception $e) {
            Log::error("Webhook handling error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    

    public function handleGatewayCallback(Request $request)
    {
        try {
            $gateway = $request->gateway;
            $reference = $request->reference;
            
            $gatewayService = $this->gatewayFactory->create($gateway);
            $payment = $gatewayService->handleCallback($reference);
            if($payment->owner_type == 'applicant'){
                return redirect(config('default.portal.domain').'application/payments?status=successful');
            }
            return redirect(config('default.portal.domain').'student/payments?status=successful');
        } catch (\Exception $e) {
            dd($e->getMessage());
            if(str_contains($e->getMessage(),'applicant')){
                return redirect(config('default.portal.domain').'application/payments?status=failed');
            }
            return redirect(config('default.portal.domain').'student/payments?status=failed');
            //return response()->json(["error" => $e->getMessage()], 400);
        }
    }


    public function verifyPayment($reference){
        $payment = Payment::where('reference', $reference)->orWhere('gateway_reference', $reference)->first();
        if(!$payment){
            return response()->json(['message' => 'Payment not found'], 404);
        }
        $payment = $payment;
        return response()->json($payment);
    }

    /**
     * Get all payments for staff management
     */
    public function getAllPayments(Request $request)
    {
        try {
            $query = Payment::with(['invoice.invoice_type', 'invoice.owner']);

            // Apply filters
            if ($request->session_id) {
                $query->whereHas('invoice', function($q) use ($request) {
                    $q->where('session_id', $request->session_id);
                });
            }

            if ($request->user_type) {
                $query->whereHas('invoice', function($q) use ($request) {
                    $q->where('owner_type', $request->user_type);
                });
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->date_range && is_array($request->date_range) && count($request->date_range) == 2) {
                $query->whereBetween('created_at', $request->date_range);
            }

            $payments = $query->orderBy('created_at', 'desc')->paginate($request->paginateBy ?? 20);
            return new ApiResource(PaymentResource::collection($payments), false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get payment statistics for staff dashboard
     */
    public function getPaymentStatistics(Request $request)
    {
        try {
            $query = Payment::query();

            // Apply same filters as getAllPayments
            if ($request->session_id) {
                $query->whereHas('invoice', function($q) use ($request) {
                    $q->where('session_id', $request->session_id);
                });
            }

            if ($request->user_type) {
                $query->whereHas('invoice', function($q) use ($request) {
                    $q->where('owner_type', $request->user_type);
                });
            }

            if ($request->date_range && is_array($request->date_range) && count($request->date_range) == 2) {
                $query->whereBetween('created_at', $request->date_range);
            }

            $statistics = [
                'total_amount' => $query->sum('amount'),
                'successful_count' => (clone $query)->where('status', 'successful')->count(),
                'pending_count' => (clone $query)->where('status', 'pending')->count(),
                'failed_count' => (clone $query)->where('status', 'failed')->count(),
            ];

            return new ApiResource($statistics, false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Export payments to Excel
     */
    public function exportPayments(Request $request)
    {
        try {
            // This would typically use Laravel Excel
            // For now, return a simple response
            return new ApiResource(['message' => 'Export functionality not implemented yet'], false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
        }
    }

    private function handleGatewayPaymentRecord($gateway, $invoice, $payment, $paymentData, $totalAmount, $wallet, $description = 'payment')
    {

        try {
            $gatewayService = $this->gatewayFactory->create($gateway);
            $response = $gatewayService->processPayment($invoice, $payment, $paymentData, $totalAmount, $wallet, $description);
            Log::info("Gateway processing response: " . json_encode($response));
            return $response;
        } catch (\Exception $e) {
            Log::error("Gateway processing error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get payment categories for filtering
     */
    public function getPaymentCategories(Request $request)
    {
        try {
            $categories = \App\Models\PaymentCategory::where('status', 'active')
                                                    ->orderBy('name')
                                                    ->get();

            return new ApiResource($categories, false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Generate comprehensive payment report
     */
    public function generatePaymentReport(Request $request)
    {
        try {
            $query = Payment::with(['invoice.invoice_type.payment_category', 'invoice.owner']);

            // Apply date range filter
            if ($request->dateRange) {
                $dates = $this->getDateRange($request->dateRange, $request->startDate, $request->endDate);
                $query->whereBetween('created_at', [$dates['start'], $dates['end']]);
            }

            // Apply other filters
            if ($request->userType) {
                $query->whereHas('invoice', function($q) use ($request) {
                    $q->where('owner_type', $request->userType);
                });
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->paymentCategory) {
                $query->whereHas('invoice.invoice_type', function($q) use ($request) {
                    $q->where('payment_category_id', $request->paymentCategory);
                });
            }

            if ($request->paymentType) {
                $query->whereHas('invoice', function($q) use ($request) {
                    $q->where('invoice_type_id', $request->paymentType);
                });
            }

            if ($request->sessionId) {
                $query->whereHas('invoice', function($q) use ($request) {
                    $q->where('session_id', $request->sessionId);
                });
            }

            $payments = $query->orderBy('created_at', 'desc')->get();

            // Add user info to each payment
            $payments->each(function($payment) {
                if ($payment->invoice && $payment->invoice->owner) {
                    $owner = $payment->invoice->owner;
                    $payment->user_info = [
                        'name' => $owner->full_name ?? ($owner->first_name . ' ' . $owner->last_name),
                        'email' => $owner->email,
                        'phone' => $owner->phone_number ?? $owner->phone
                    ];
                }
            });

            // Generate summary
            $summary = [
                'total_revenue' => $payments->where('status', 'successful')->sum('amount'),
                'successful_count' => $payments->where('status', 'successful')->count(),
                'pending_count' => $payments->where('status', 'pending')->count(),
                'failed_count' => $payments->where('status', 'failed')->count(),
                'total_count' => $payments->count(),
                'success_rate' => $payments->count() > 0 ? round(($payments->where('status', 'successful')->count() / $payments->count()) * 100, 2) : 0
            ];

            // Category breakdown
            $categoryBreakdown = $payments->where('status', 'successful')
                                        ->groupBy('invoice.invoice_type.payment_category.name')
                                        ->map(function($group, $categoryName) use ($summary) {
                                            $amount = $group->sum('amount');
                                            return [
                                                'name' => $categoryName ?: 'Uncategorized',
                                                'amount' => $amount,
                                                'count' => $group->count(),
                                                'percentage' => $summary['total_revenue'] > 0 ? round(($amount / $summary['total_revenue']) * 100, 2) : 0,
                                                'color' => $this->generateColor($categoryName)
                                            ];
                                        })->values();

            // User type breakdown
            $userTypeBreakdown = $payments->where('status', 'successful')
                                        ->groupBy('invoice.owner_type')
                                        ->map(function($group, $userType) {
                                            return [
                                                'name' => ucfirst($userType) . 's',
                                                'amount' => $group->sum('amount'),
                                                'count' => $group->count(),
                                                'color' => $userType === 'student' ? '#3B82F6' : '#8B5CF6'
                                            ];
                                        })->values();

            $reportData = [
                'payments' => $payments,
                'summary' => $summary,
                'category_breakdown' => $categoryBreakdown,
                'user_type_breakdown' => $userTypeBreakdown
            ];

            return new ApiResource($reportData, false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Export payment report to Excel
     */
    public function exportPaymentReport(Request $request)
    {
        try {
            // This would typically use Laravel Excel to generate Excel file
            // For now, return a simple response
            return new ApiResource(['message' => 'Export functionality not implemented yet'], false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get comprehensive revenue analytics
     */
    public function getRevenueAnalytics(Request $request)
    {
        try {
            $period = $request->period ?? 30;
            $startDate = now()->subDays($period)->startOfDay();
            $endDate = now()->endOfDay();

            $query = Payment::with(['invoice.invoice_type.payment_category', 'invoice.owner'])
                           ->whereBetween('created_at', [$startDate, $endDate]);

            $allPayments = $query->get();
            $successfulPayments = $allPayments->where('status', 'successful');

            // Overview statistics
            $overview = [
                'total_revenue' => $successfulPayments->sum('amount'),
                'transaction_count' => $successfulPayments->count(),
                'total_payments' => $allPayments->count(),
                'successful_payments' => $successfulPayments->count(),
                'avg_transaction' => $successfulPayments->count() > 0 ? $successfulPayments->avg('amount') : 0,
                'success_rate' => $allPayments->count() > 0 ? round(($successfulPayments->count() / $allPayments->count()) * 100, 2) : 0,
                'daily_average' => $successfulPayments->sum('amount') / $period,
                'revenue_growth' => $this->calculateGrowthRate($period),
                'max_daily_revenue' => $this->getMaxDailyRevenue($startDate, $endDate)
            ];

            // Daily revenue trend
            $dailyRevenue = $successfulPayments->groupBy(function($payment) {
                return $payment->created_at->format('Y-m-d');
            })->map(function($group, $date) {
                return [
                    'date' => $date,
                    'amount' => $group->sum('amount'),
                    'count' => $group->count()
                ];
            })->values();

            // Category breakdown
            $categoryBreakdown = $successfulPayments->groupBy('invoice.invoice_type.payment_category.name')
                                                  ->map(function($group, $categoryName) use ($overview) {
                                                      $amount = $group->sum('amount');
                                                      return [
                                                          'name' => $categoryName ?: 'Uncategorized',
                                                          'amount' => $amount,
                                                          'count' => $group->count(),
                                                          'percentage' => $overview['total_revenue'] > 0 ? round(($amount / $overview['total_revenue']) * 100, 2) : 0,
                                                          'color' => $this->generateColor($categoryName)
                                                      ];
                                                  })->values();

            // User type analysis
            $userTypeAnalysis = $successfulPayments->groupBy('invoice.owner_type')
                                                  ->map(function($group, $userType) {
                                                      return [
                                                          'type' => $userType,
                                                          'total_amount' => $group->sum('amount'),
                                                          'payment_count' => $group->count(),
                                                          'avg_amount' => $group->avg('amount'),
                                                          'success_rate' => 100, // These are already successful payments
                                                          'color' => $userType === 'student' ? '#3B82F6' : '#8B5CF6'
                                                      ];
                                                  })->values();

            // Gateway analysis
            $gatewayAnalysis = $allPayments->groupBy('gateway')
                                          ->map(function($group, $gateway) {
                                              $successful = $group->where('status', 'successful');
                                              return [
                                                  'name' => $gateway ?: 'Direct Payment',
                                                  'total_amount' => $successful->sum('amount'),
                                                  'transaction_count' => $group->count(),
                                                  'success_rate' => $group->count() > 0 ? round(($successful->count() / $group->count()) * 100, 2) : 0,
                                                  'avg_amount' => $successful->count() > 0 ? $successful->avg('amount') : 0
                                              ];
                                          })->values();

            // Top payment types
            $topPaymentTypes = $successfulPayments->groupBy('invoice.invoice_type.id')
                                                 ->map(function($group, $typeId) {
                                                     $firstPayment = $group->first();
                                                     return [
                                                         'id' => $typeId,
                                                         'name' => $firstPayment->invoice->invoice_type->name ?? 'Unknown',
                                                         'category_name' => $firstPayment->invoice->invoice_type->payment_category->name ?? null,
                                                         'total_revenue' => $group->sum('amount'),
                                                         'payment_count' => $group->count(),
                                                         'avg_amount' => $group->avg('amount'),
                                                         'success_rate' => 100 // These are already successful payments
                                                     ];
                                                 })->sortByDesc('total_revenue')->take(10)->values();

            $analytics = [
                'overview' => $overview,
                'daily_revenue' => $dailyRevenue,
                'category_breakdown' => $categoryBreakdown,
                'user_type_analysis' => $userTypeAnalysis,
                'gateway_analysis' => $gatewayAnalysis,
                'top_payment_types' => $topPaymentTypes
            ];

            return new ApiResource($analytics, false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Helper method to get date range based on filter
     */
    private function getDateRange($dateRange, $startDate = null, $endDate = null)
    {
        $now = now();

        switch ($dateRange) {
            case 'today':
                return ['start' => $now->startOfDay(), 'end' => $now->endOfDay()];
            case 'yesterday':
                return ['start' => $now->subDay()->startOfDay(), 'end' => $now->subDay()->endOfDay()];
            case 'this_week':
                return ['start' => $now->startOfWeek(), 'end' => $now->endOfWeek()];
            case 'last_week':
                return ['start' => $now->subWeek()->startOfWeek(), 'end' => $now->subWeek()->endOfWeek()];
            case 'this_month':
                return ['start' => $now->startOfMonth(), 'end' => $now->endOfMonth()];
            case 'last_month':
                return ['start' => $now->subMonth()->startOfMonth(), 'end' => $now->subMonth()->endOfMonth()];
            case 'this_year':
                return ['start' => $now->startOfYear(), 'end' => $now->endOfYear()];
            case 'custom':
                return [
                    'start' => $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : $now->subMonth()->startOfDay(),
                    'end' => $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : $now->endOfDay()
                ];
            default:
                return ['start' => $now->subMonth()->startOfDay(), 'end' => $now->endOfDay()];
        }
    }

    /**
     * Generate color for categories
     */
    private function generateColor($name)
    {
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#84CC16', '#F97316'];
        return $colors[crc32($name ?? 'default') % count($colors)];
    }

    /**
     * Calculate growth rate compared to previous period
     */
    private function calculateGrowthRate($period)
    {
        try {
            $currentStart = now()->subDays($period)->startOfDay();
            $currentEnd = now()->endOfDay();
            $previousStart = now()->subDays($period * 2)->startOfDay();
            $previousEnd = $currentStart->copy()->endOfDay();

            $currentRevenue = Payment::where('status', 'successful')
                                   ->whereBetween('created_at', [$currentStart, $currentEnd])
                                   ->sum('amount');

            $previousRevenue = Payment::where('status', 'successful')
                                    ->whereBetween('created_at', [$previousStart, $previousEnd])
                                    ->sum('amount');

            if ($previousRevenue > 0) {
                return round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2);
            }

            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get maximum daily revenue in the period
     */
    private function getMaxDailyRevenue($startDate, $endDate)
    {
        try {
            return Payment::where('status', 'successful')
                         ->whereBetween('created_at', [$startDate, $endDate])
                         ->selectRaw('DATE(created_at) as date, SUM(amount) as daily_total')
                         ->groupBy('date')
                         ->orderByDesc('daily_total')
                         ->first()
                         ->daily_total ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

}
