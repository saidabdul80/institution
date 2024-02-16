<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Modules\Staff\Services\InvoiceService;
use Modules\Staff\Transformers\UtilResource;

class InvoiceController extends Controller
{
    private $invoiceService;
     public function __construct(InvoiceService $invoiceService) {
        $this->invoiceService = $invoiceService;
    }

    public function exportInvoice(Request $request){        
        try{         
            $request->validate([                                                           
                "session_id" =>"required",
                "from" =>"required",
                "to" =>"required"
            ]);   
            return $this->invoiceService->exportInvoice($request);                    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

}