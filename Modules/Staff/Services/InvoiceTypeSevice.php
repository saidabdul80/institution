<?php
namespace Modules\Staff\Services;

use App\Exports\InvoiceByRangeExport;
use App\Exports\InvoiceBySessionExport;
use App\Models\PaymentCategory;
use Modules\Staff\Repositories\InvoiceTypeRepository;
use Exception;

use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Staff\Services\Utilities;

class InvoiceTypeSevice extends Utilities{

    private $invoiceTypeRepository;    
    private $user;
    private $carbon;
    private $paymentCategory;
    public function __construct( InvoiceTypeRepository $invoiceTypeRepository, Carbon $carbon, PaymentCategory $paymentCategory)
    {        

        $this->invoiceTypeRepository = $invoiceTypeRepository;                              
        $this->carbon = $carbon;
        $this->paymentCategory = $paymentCategory;
    }


    public function create($request){        
        //return $request->all();
        if(!$this->invoiceTypeRepository->exists($request->all())){            
            $this->invoiceTypeRepository->create($request->all());                   
            return 'success';
        }
        throw new \Exception('InvoiceType already exists', 404);
    }

    public function update($request){        
        if(!$this->invoiceTypeRepository->existsInOthers($request->get('id'), $request->all())){
            $data = $request->all();
            $id = $data['id'];
            unset($data['id']);
            $this->invoiceTypeRepository->update($id,$data);                   
            return 'success';
        }
        throw new \Exception('InvoiceType already exists', 404);
    }
    
    public function delete($request){        
        return $this->invoiceTypeRepository->delete($request->get('id'));        
    }
    
    public function InvoiceTypes($request){               
        return $this->invoiceTypeRepository->fetch(session_id:$request->get('session_id')??$request->session_id,paginateBy: $request->get('paginateBy'));
    }

    public function allPaid($request, $type){
        $id = $request->get('id');
        $session_id = $request->get('session_id');
        $paginateBy = $request->get('paginateBy')?? 500;
        $byDate = $request->get('date_name'); //now, month and year, refers to current and null refers to specifying date range
        $byDate_from = '';
        $byDate_to = '';
        
        if(!is_null($byDate)){
            if($byDate != 'now' AND $byDate != 'year' AND $byDate != 'month'){
                throw new \Exception('invalid date name value');
            }

            if($byDate == 'now'){
                $byDate_from = $byDate_to = $this->carbon::now()->format('Y-m-d');                
            }
            if($byDate == 'month'){
                $now = $this->carbon::now();
                $byDate_from = $now->startOfMonth()->format('Y-m-d');
                $byDate_to = $now->endOfMonth()->format('Y-m-d');                                
            }

            if($byDate == 'year'){
                $now = $this->carbon::now();                                
                $byDate_from = $now->startOfYear()->format('Y-m-d');
                $byDate_to = $now->endOfYear()->format('Y-m-d');                                
            }

        }else{
           
            $now = $this->carbon::now()->format('Y-m-d');
            $byDate_from = $request->get('from')??$now;
            $byDate_to = $request->get('to')??$now;            
        }

            $data = $this->invoiceTypeRepository->getPaid($id,$session_id, $byDate_from, $byDate_to,$type);            
            $invoiceDataByPagination = $this->invoiceTypeRepository->getPaidPaginate($id,$session_id, $paginateBy, $byDate_from, $byDate_to);
            
            $ids = array_column($data->toArray(), 'owner_id'); /** @var $ids is either student id or applicant id  */
            
            if($type =='applicant'){
     
                if(sizeof($ids)>0){
                    return ['user'=>$this->invoiceTypeRepository->applicantsData($ids, $paginateBy), 'invoices'=>$invoiceDataByPagination];
                }else{
                    throw new \Exception('No Applicant found to have paid', 404);
                }
            }
    
            if($type =='student'){
                if(sizeof($ids)>0){
                    return ['user' =>$this->invoiceTypeRepository->studentsData($ids, $paginateBy), 'invoice'=>$invoiceDataByPagination];
                }else{
                    throw new \Exception('No Student found to have paid', 404);
                }
            }
    }

    public function getInvoiceTypes($payment_name){
        $payment_category = $this->invoiceTypeRepository->paymentCategory($payment_name);
        if(empty($payment_category)){
            throw new \Exception('no payment category name found', 404);
        }

        $payment_category_id = $payment_category->id;
        return $this->invoiceTypeRepository->InvoiceTypesByPaymentCategoryId($payment_category_id);        
    }

    public function processTotalPaid($request){
        $id = $request->get('invoice_type_id');
        $session_id = $request->get('session_id');
        $paginateBy = $request->get('paginateBy')?? 500;
        $byDate = $request->get('date_name'); //now, month and year, refers to current and null refers to specifying date range
        $byDate_from = '';
        $byDate_to = '';
        
        if(!is_null($byDate)){
            if($byDate != 'now' AND $byDate != 'year' AND $byDate != 'month'){
                throw new \Exception('invalid date name value');
            }

            if($byDate == 'now'){
                $byDate_from = $byDate_to = $this->carbon::now()->format('Y-m-d');                
            }
            if($byDate == 'month'){
                $now = $this->carbon::now();
                $byDate_from = $now->startOfMonth()->format('Y-m-d');
                $byDate_to = $now->endOfMonth()->format('Y-m-d');                                
            }

            if($byDate == 'year'){
                $now = $this->carbon::now();                                
                $byDate_from = $now->startOfYear()->format('Y-m-d');
                $byDate_to = $now->endOfYear()->format('Y-m-d');                                
            }

        }else{           
            $now = $this->carbon::now()->format('Y-m-d');
            $byDate_from = $request->get('from')??$now;
            $byDate_to = $request->get('to')??$now;            
        }

        return $this->invoiceTypeRepository->getPaidTotal($id,$session_id, $byDate_from, $byDate_to);        
    }

    public function getTotalPayByPaymentName($request){
        $payment_category = $this->invoiceTypeRepository->paymentCategory($request->get('payment_name'));
        if(empty($payment_category)){
            throw new \Exception('no payment category name found', 404);
        }

        $payment_category_id = $payment_category->id;
        $invoiceTypes = $this->invoiceTypeRepository->InvoiceTypesByPaymentCategoryId($payment_category_id);        
        $ids = array_column($invoiceTypes,'id');
        
        $session_id = $request->get('session_id');

        $byDate = $request->get('date_name'); //now, month and year, refers to current and null refers to specifying date range
        $byDate_from = '';
        $byDate_to = '';
        
        if(!is_null($byDate)){
            if($byDate != 'now' AND $byDate != 'year' AND $byDate != 'month'){
                throw new \Exception('invalid date name value');
            }

            if($byDate == 'now'){
                $byDate_from = $byDate_to = $this->carbon::now()->format('Y-m-d');                
            }
            if($byDate == 'month'){
                $now = $this->carbon::now();
                $byDate_from = $now->startOfMonth()->format('Y-m-d');
                $byDate_to = $now->endOfMonth()->format('Y-m-d');                                
            }

            if($byDate == 'year'){
                $now = $this->carbon::now();                                
                $byDate_from = $now->startOfYear()->format('Y-m-d');
                $byDate_to = $now->endOfYear()->format('Y-m-d');                                
            }

        }else{
           
            $now = $this->carbon::now()->format('Y-m-d');
            $byDate_from = $request->get('from')??$now;
            $byDate_to = $request->get('to')??$now;            
        }
        return $this->invoiceTypeRepository->getPaidTotalForManyInvoiceTypes($ids,$session_id, $byDate_from, $byDate_to);    

    }

    public function confirmInvoicePayment($request){
        $invoice_number = $request->get('invoice_number');
        
        if($this->invoiceTypeRepository->invoiceData($invoice_number)->status ==='paid'){
            throw new \Exception('Payment has already been made', 400);
        }

        return $this->invoiceTypeRepository->confirmInvoicePayment($invoice_number);    
    }

    public function udateStatus($request){
        return $this->invoiceTypeRepository->update($request->get("id"), ["status"=>$request->get("status")]);        
    }

   

}
