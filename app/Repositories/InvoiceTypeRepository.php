<?php

namespace App\Repositories;

use App\Models\InvoiceType;
use Illuminate\Support\Facades\DB;

class InvoiceTypeRepository
{
    protected $invoiceType;

    public function __construct(InvoiceType $invoiceType)
    {
        $this->invoiceType = $invoiceType;
    }

    public function getByIdOrCategory($id = null, $category_id = null, $category = null)
    {
        return $this->invoiceType->where('id', $id)->orWhere('payment_category_id', $category_id)->orWhereHas('payment_category', function ($q) use ($category) {
            $q->where('short_name', $category);
        })->latest()->first();
    }

    private function group_by($array, $key) {
        $return = [];
        
        foreach($array as $k => $val) {
            $return[$val[$key]][] = $val;         
        }        
        return $return;
    }
    public function filterInvoiceType($invoiceTypes, $property, $value){                
        $groupedInvoiceTypes = $this->group_by($invoiceTypes,"payment_short_name");
        $filteredInvoiceTypes = [];
        foreach($groupedInvoiceTypes as $key => $payment){
            $is_null = true;
            if(sizeof($payment) >1){     
                collect($payment)->each(function($item) use($property,$value,&$is_null){
                    if(!is_null($item[$property])){
                        $is_null = false;
                    }       
                });
                if($is_null){                                        
                    array_push($filteredInvoiceTypes,  ...$payment);
                }else{
                    array_push($filteredInvoiceTypes, ...collect($payment)->where($property,$value)->toArray());
                }                           
            }else{                  
                $filteredInvoiceTypes[] = $payment[0];
            }
        }
        return $filteredInvoiceTypes;
    }

    public function  getPaymentDetails($owner,$session_id = null){
        
        $session_id = $session_id?? $owner->session_id;
        $semester_id = (int) DB::table('configurations')->where('name','current_semester')->first()?->value;              
        $query = [
            "gender" => $owner->gender,
            "owner_type" => $owner->user_type,
            "programme_id" => $owner->programme_id ?? $owner->applied_programme_id,
            "programme_type_id" => $owner->programme_type_id,
            "department_id" => $owner->department_id,
            "faculty_id" => $owner->faculty_id,
            "entry_mode_id" => $owner->mode_of_entry_id,
            "state_id" => $owner->state_id,
            "lga_id" => $owner->lga_id,
            "level_id" => $owner->level_id,
            "country_id" => $owner->country_id,
            "session_id" => $session_id,
            "semester_id" =>$semester_id
        ];
                
        $invoiceTypes = $this->invoiceType::with('payment_category')->match($query)->where('status', 'Active')->latest()->get()->toArray();
        
        $invoiceTypes = $this->filterInvoiceType($invoiceTypes,"country_id",$owner->country_id);
        $invoiceTypes = $this->filterInvoiceType($invoiceTypes,"state_id",$owner->state_id);        
        $invoiceTypes = $this->filterInvoiceType($invoiceTypes,"lga_id",$owner->lga_id);     
                
        if(empty($invoiceTypes)){
            throw new \Exception("Sorry, no payment setup for you yet", 404);
        }
        
        $ownerInvoice = DB::table('invoices')
                                ->whereIn('invoice_type_id',array_column($invoiceTypes,'id'))
                                ->where(['owner_id'=> $owner['id'], 'session_id'=>$session_id, "owner_type"=>"applicant"])
                                ->get();         
                                                
        foreach($invoiceTypes as $key => &$invoiceType){                                    
            if(count($ownerInvoice->where("status","paid")->where("invoice_type_id",$invoiceType['id']))>0){
                $invoiceType['status'] = 'paid';
            }else{
                $invoiceType['status'] = 'unpaid';                
            }
        }
    
        $response = $invoiceTypes;
        if(!is_null($response)){
            return $response;
        }
    
    }
        
    public function getPaymentCategoryOfInvoiceTypeId($invoice_type_id){
        $payment_category = $this->invoiceType::with('payment_category')->find($invoice_type_id)?->payment_category;   
        if($payment_category){
            return $payment_category;
        }
        throw new \Exception('Payment not Found',404);
    }
}
