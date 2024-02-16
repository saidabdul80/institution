<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Modules\Staff\Services\InvoiceTypeSevice;
use Modules\Staff\Transformers\UtilResource;


class InvoiceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $InvoiceTypeService;
    public function __construct(InvoiceTypeSevice $InvoiceTypeService)
    {
        $this->InvoiceTypeService = $InvoiceTypeService;
    }

    public function create(Request $request){
        
        try{

            $request->validate([                                                           
                "name" =>"required",
                "amount"=>"required",
                "payment_category_id" => "required",
                "owner_type" =>"required"
            ]);        
                          
            $response = $this->InvoiceTypeService->create($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function update(Request $request){
        
        try{

            $request->validate([                                           
                "id" =>"required",
                "name" =>"required",
                "amount"=>"required",
                "payment_category_id" => "required",
                "owner_type" =>"required"
            ]);        
                          
            $response = $this->InvoiceTypeService->update($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function delete(Request $request){        
        try{

            $request->validate([                                                           
                "id" =>"required"
            ]);        
                          
            $response = $this->InvoiceTypeService->delete($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }        
    }

    public function udateStatus(Request $request){
        
        try{

            $request->validate([                                                           
                "id" =>"required",
                "status"=>"required"
            ]);        
                          
            $response = $this->InvoiceTypeService->udateStatus($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }


    
    public function getInvoiceTypes(Request $request){
        
        try{                
                          
            $response = $this->InvoiceTypeService->InvoiceTypes($request);        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getPaidApplicant(Request $request){
        
        try{          
            $request->validate([                                           
                "id" =>"required",
                "session_id" =>"required",
            ]);       
                          
            $response = $this->InvoiceTypeService->allPaid($request, 'applicant');        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    /**
     * @var int $request->get('id') -- invoice type id
     */
    public function getPaidStudent(Request $request){
        
        try{         

            $request->validate([                                           
                "id" =>"required",
                "session_id" =>"required",
            ]);       
                             
            $response = $this->InvoiceTypeService->allPaid($request, 'student');        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    
     public function getInvoiceTypeByCategory(Request $request){
        
        try{         

            $request->validate([                                                           
                "payment_name" =>"required",
            ]);       
                             
            $response = $this->InvoiceTypeService->getInvoiceTypes($request->get('payment_name'));        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    
     public function getTotalPaidByInvoiceType(Request $request){
        
        try{         

            $request->validate([                                           
                "invoice_type_id" =>"required", //invoice type id
                "session_id" =>"required",
            ]);       
                             
            $response = $this->InvoiceTypeService->processTotalPaid($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getTotalPayByPaymentName(Request $request){
        
        try{         

            $request->validate([                                           
                
                "session_id" =>"required",
                "payment_name" =>"required"
            ]);       
                             
            $response = $this->InvoiceTypeService->getTotalPayByPaymentName($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function manualInvoicePaymentConfirmation(Request $request){
        
        try{         

            $request->validate([                                                           
                "invoice_number" =>"required"
            ]);       
                             
            $response = $this->InvoiceTypeService->confirmInvoicePayment($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

       

}