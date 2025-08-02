<?php
 namespace Modules\Application\Repositories;

use App\Models\Applicant;
use Illuminate\Validation\ValidationException;
 use Illuminate\Support\Facades\DB;
 use App\Models\Payment;
 use App\Models\PaymentCategory;
 use App\Models\InvoiceType;
 use App\Models\Invoice;
use App\Models\Faculty;

 class PaymentRepository{

    private $payment;
    private $paymentCategory;
    private $invoiceType;
    private $invoice;
    private $faculty;

    private $applicant;
    public function __construct(
        Payment $payment,
        PaymentCategory $paymentCategory,
        InvoiceType $invoiceType,
        Invoice $invoice,
        Faculty $faculty,    
        Applicant  $applicant
        )
    {
        $this->payment = $payment;
        $this->paymentCategory = $paymentCategory;
        $this->invoiceType = $invoiceType;
        $this->invoice = $invoice;
        $this->faculty = $faculty;    
        $this->applicant = $applicant;
    }

    public function storeDetails($request)
    {
        $owner_id = $request->get('applicant_id');
        $invoice_id= $request->get('invoice_id');
        $amount = $request->get('amount');
        $save = new $this->payment;
        $save->amount = $amount;
        $save->payment_reference = $request->get('payment_reference');
        $save->payment_channel = $request->get('payment_channel');
        $save->gate_way_response = $request->get('gateway_response');
        $save->transaction_id = $request->get('transaction_id');
        $save->jtr = $request->get('jtr');
        $save->status = $request->get('status');
        $save->invoice_id = $invoice_id;
        $save->owner_id = $owner_id;
        $save->owner_type = 'applicant';
        $save->session_id = $request->get('session_id');
        $save->payment_mode = $request->get('payment_mode');

        $invoiceAmount = $this->invoice::where('id',$invoice_id)->first()->amount;
        if($amount >= $invoiceAmount ){
            //mark invoice as paid
            $this->invoice::where('id',$request->get('invoice_id'))->update(['status'=>'paid']);
        }else{
            $payment = DB::table('payments')->selectRaw('sum(amount) as totalPaid, owner_id,owner_type,invoice_id,amount')->where(['owner_id'=>$owner_id, 'owner_type'=>'applicant', 'invoice_id'=>$invoice_id])->get();
            if(!is_null($payment)){
                $totalPaid = $payment->totalPaid + $amount;
                if($totalPaid >= $invoiceAmount){
                    //mark invoice as paid
                    $this->invoice::where('id',$request->get('invoice_id'))->update(['status'=>'paid']);
                }
            }
        }

        if($save->save()){           
            return $save;
        }
        throw new \Exception("Failed to complete, please requery payment",404);
    }

    public function getDistinctPaymentsByApplicants($request){
        return Payment::where(["owner_id" =>$request->get('applicant_id'), "session_id" =>$request->get('session_id'), "owner_type"=>'applicant'])->latest()->get();
    }

    public function getLastInvoiceNumber($tablename){
        $response =  DB::table($tablename,'tablename')->orderBy('id', 'desc')->latest()->first();
        if($response){
            return $response->invoice_number;
        }else{
            return -1;
        }

     }

     public function invoiceExist($applicant_id, $session_id, $payment_name, $query){

        $payment_category_id = $this->getPaymentId($payment_name);
        $response = $this->invoiceType::where("payment_category_id", $payment_category_id)->match($query)->latest()->first();
        if($response){
            return $this->invoice::where(['invoice_type_id'=>$response->id, 'session_id'=>$session_id,'owner_id'=>$applicant_id])->first();
        }

        throw new \Exception("Invoice Template Not Found",404);

     }

    public function applicantInvoices($session_id, $applicant_id)
    {
            return $this->invoice::where(['session_id' => $session_id,'owner_type'=>'applicant', 'owner_id' => $applicant_id])
                        ->with(['payment', 'invoice_type'=>function($query){
                                $query->where('owner_type', 'applicant');
                     }])->get();
    }

    public function applicantInvoicesById($applicant_id)
    {
            return $this->invoice::where(['owner_type'=>'applicant', 'owner_id' => $applicant_id])
                        ->with(['payment', 'invoice_type'=>function($query){
                                $query->where('owner_type', 'applicant');
                     }])->latest()->get();
    }

    public function applicantPayments($session_id, $applicant_id)
    {
        return $this->payment::where(['session_id' => $session_id, 'owner_id' => $applicant_id, 'owner_type' => 'applicant'])->with('invoice')->latest()->get();
    }

    private function getPaymentId($short_name){
        $data = $this->paymentCategory::where('short_name',$short_name)->first();
        if($data){
            return $data->id;
        }
        throw new \Exception("Invalid Payment Name",404);
    }

    public function getInvoice($applicant_id,$invoice_number,$session_id,$payment_name,$query)
    {
        $payment_category_id = $this->getPaymentId($payment_name);

        /* fetch invoice type data */
        $response = $this->invoiceType::where("payment_category_id", $payment_category_id)->match($query)->latest()->first();

        /* save applicant invoice */
        $save = new $this->invoice;
        $save->owner_id = $applicant_id;
        $save->owner_type = 'applicant';
        $save->invoice_type_id = $response->id;
        $save->session_id = $session_id;
        $save->invoice_number = $invoice_number;
        $save->amount = $response->amount;
        $save->description = $response->description;

        if($save->save()){
            return $save;
        }
    }

    public function getFacultyByDepartmentId($id)
    {
        return $this->faculty::find($id)->id;
    }


    public function getAllInvoiceTypes($query)
    {
        return $this->invoiceType::match($query)->with('payment_category')->get();
    }
    
 }



