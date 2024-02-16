<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Modules\SecuredPanelAPI\Services\Utilities;

class PromoteStudent implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $invoice;
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
       
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {                         
        $invoice = $this->invoice;
        if($invoice->owner_type == 'student'){  
                          
            $graduation_level_id = $invoice->owner->graduation_level_id;
            $programme_max_duration = $invoice->owner->programme_max_duration;
    
            $final_semester_id = DB::table('configurations')->where('name','final_semester')->first()->value;                           
            $invoice_semester_id = $invoice->invoiceType()->semester_id;
            
            if(!empty($invoice_semester_id)){
                if($invoice_semester_id == $final_semester_id){
                    $promote = true;
                }else{
                    $promote = false;
                }
            }else{
                $promote = true;
            }
            
            if( $invoice->status == 'paid' &&                    
                $invoice->paymentCategoryName() == 'registration_fee' &&
                $promote == true
            )
            {
                
                //promote to spill or promote to next level
                $current_session = Utilities::currentSession();               
                $token = 'student'.$invoice->owner->id.$invoice->invoiceType()->session_id;
                $student_enrolment_record = [
                    "owner_id"=> $invoice->owner->id,
                    "session_id"=>$current_session,
                    "level_id_from"=> $invoice->owner->level_id,                    
                    "token"=> $token,
                    "created_at"=> date('Y-m-d h:i:s'),
                    "updated_at"=> date('Y-m-d h:i:s'),                                
                ]; 
                
                $updates = [
                    'promote_count'=> $invoice->owner->promote_count+1,                    
                ];
    
                $check  = false;
                $updateStudentTable = false;
    
                $graduation_level_order = DB::table('levels')->where('id',$graduation_level_id)->first()->order;
                $current_level_order = DB::table('levels')->where('id',$invoice->owner->level_id)->first()->order;
                if($current_level_order == 'spill'){
                    $current_level_order = '10';
                }
                if((int) $current_level_order >= (int) $graduation_level_order){
                    //promote to spill or withdraw
                    if((int) $current_level_order > (int) $graduation_level_order ){
                        $spill_id = $invoice->owner->level_id; //maintaining the spill id                      
                    }else{
                        $spill_id = DB::table('levels')->where('order', 'spill')->first()->id; //selecting the spill id
                    }
                    
                    $next_level_id = $spill_id;                         
                    if($invoice->owner->promote_count >= $programme_max_duration){
                        //withdraw student
                        unset($updates['promote_count']);
                        $updates['status']= 'academic withdrawal';  
                        $updateStudentTable = true;                                                                 
                    }else{
                        //promte to student spill
                        $updates['level_id']= $spill_id;                            
                    }                                            
                    $check = DB::table('student_enrollments')->where(['token'=>$token])->exists();
    
                }else{
                    
                    //promote to next level
                    $level_id = $invoice->owner->level_id;      
                    $level = DB::table('levels')->where('id',$level_id)->first();                
                    $nextOrder = strval($level->order +1);                
                    $next_level = DB::table('levels')->where('order',$nextOrder)->first();                
                    $next_level_id = $next_level->id;              
                    $updates['level_id']= $next_level_id;
                    $check = DB::table('student_enrollments')->where(['token'=>$token])->exists();                
                    
                }            
                
                $student_enrolment_record["level_id_to"] = $next_level_id;                   
                DB::transaction(function() use($invoice,$student_enrolment_record, $updates,$check, $updateStudentTable){                    
                    if($updateStudentTable == true){                   
                        DB::table('students')->where('id', $invoice->owner->id)->update($updates);         
                    }
                                        
                    if($check == false){
                        DB::table('students')->where('id', $invoice->owner->id)->update($updates);                                 
                        DB::table('student_enrollments')->upsert([$student_enrolment_record], 'token');
                    }         
                });
                return 'success';
                
            }            
        }  
      
    }
}
