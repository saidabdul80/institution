<?php
namespace Modules\Staff\Repositories;

use App\Events\InvoicePaid;
use App\Models\Applicant;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\InvoiceType;
use App\Models\PaymentCategory;
use App\Models\Student;
use Carbon\Carbon;

class InvoiceTypeRepository{


    private $invoiceType;
    private $invoice;
    private $applicant;
    private $student;
    private $paymentCategory;
    public function __construct(InvoiceType $invoiceType, Invoice $invoice, Applicant $applicant, Student $student, PaymentCategory $paymentCategory)
    {
        $this->invoiceType = $invoiceType;
        $this->invoice = $invoice;
        $this->applicant = $applicant;
        $this->student = $student;
        $this->paymentCategory = $paymentCategory;

    }

    public function exists($request){
        return $this->invoiceType::where($request)->exists();

    }

    public function create($data)
    {
        return $this->invoiceType::insert($data);
    }

    public function update($id, $data)
    {
        return $this->invoiceType::where('id',$id)->update($data);
    }

    public function delete($id){
        if($this->invoiceType->where(['id'=>$id,'status'=>'Active'])->exists()){
            throw new \Exception('Invoice Type cannot be deleted, Please Deactivate it.', 404);
        }
        $this->invoiceType->find($id)->delete();
        return 'success';
    }

    public function existsInOthers($id,$data){
        unset($data['id']);
       return $this->invoiceType::where($data)->whereNotIn('id', [$id])->exists();;
    }

    public function fetch($session_id=null,$paginateBy = null){
        $paginateBy = $paginateBy?? 100;
        if($session_id == null){
            return $this->invoiceType::orderBy('id','desc')->paginate($paginateBy);
        }
        return $this->invoiceType::where('session_id', $session_id)->latest()->get();
    }

    public function getPaid($id,$session_id,$byDate_from, $byDate_to)
    {
        return DB::table('invoices')->whereRaw("status='paid' AND invoice_type_id=$id AND session_id = $session_id AND SUBSTRING_INDEX(  paid_at ,' ',1) >= '$byDate_from' AND SUBSTRING_INDEX(  paid_at ,' ',1) <= '$byDate_to'")->get();
    }

    public function getPaidPaginate($id,$session_id, $paginate,$byDate_from, $byDate_to)
    {
        return DB::table('invoices')->whereRaw("status='paid' AND invoice_type_id=$id AND session_id = $session_id AND SUBSTRING_INDEX(  paid_at ,' ',1) >= '$byDate_from' AND SUBSTRING_INDEX(  paid_at ,' ',1) <= '$byDate_to'")->paginate($paginate);
    }

    public function applicantsData($ids,$paginate)
    {
        return $this->applicant::with('alevel','olevel')->whereIn('id',$ids)->paginate($paginate);
    }

    public function studentsData($ids,$paginate)
    {

        return $this->student::with('alevel','olevel')->whereIntegerInRaw('id',$ids)->paginate($paginate);
    }

    public function paymentCategory($payment_name){
        return $this->paymentCategory::where('short_name', $payment_name)->first();
    }

    public function InvoiceTypesByPaymentCategoryId($payment_category_id){
        return $this->invoiceType::where('payment_category_id',$payment_category_id)->get();
    }

    public function getPaidTotal($id,$session_id,$byDate_from, $byDate_to){
        return DB::select(DB::raw("select sum(amount) as total from invoices WHERE status='paid' AND invoice_type_id=$id AND session_id = $session_id AND SUBSTRING_INDEX(  paid_at ,' ',1)>= '$byDate_from' AND SUBSTRING_INDEX(  paid_at ,' ',1) <= '$byDate_to'"));
    }

    public function getPaidTotalForManyInvoiceTypes($ids, $session_id,$byDate_from, $byDate_to){
        return DB::select(DB::raw("select sum(amount) as total from invoices WHERE status='paid' AND invoice_type_id IN $ids AND session_id = $session_id AND SUBSTRING_INDEX(  paid_at ,' ',1)>= '$byDate_from' AND SUBSTRING_INDEX(  paid_at ,' ',1) <= '$byDate_to'"));
    }

    public function confirmInvoicePayment($invoice_number){

        $invoice = $this->invoiceData($invoice_number);
        $this->invoice::where('id', $invoice->id)->update([
             "status" => "paid",
             "confirmed_by" => auth('api-staff')->id(),
             "updated_at" => now()
         ]);
        event(new InvoicePaid($invoice));
        return 'Successful';
    }

    public function invoiceData($invoice_number){
        $invoice = $this->invoice::where('invoice_number', $invoice_number)->first();
        if(!$invoice){
            throw new \Exception('Invoice Number Not Found', 404);
        }
        return $invoice;
    }
    
    public function invoiceTypeIds($payment_category_id, $session_id){
        return $this->invoiceType::where(['payment_category_id'=> $payment_category_id,'session_id'=>$session_id ])->pluck('id');
    }

}
