<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class GraduateStudent implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    
    public $student;
    public $student_enrolment_record;
    public function __construct($student_enrolment_record, $student)
    {
        //            
        $this->student = $student;
        $this->student_enrolment_record = $student_enrolment_record;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {                  
       
            DB::transaction(function(){
                if($this->student->graduation_level_id == $this->student->level_id){                    
                    $TCUE = DB::table('results')->selectRaw('sum(tcue) as tcue')->where('student_id', $this->student_enrolment_record['owner_id'])->get()[0]?->tcue ??0;
                    if($TCUE >= $this->student->min_credit_unit_req){
                        DB::table('students')->where('id', $this->student_enrolment_record['owner_id'])->update(['status'=>'graduated']);
                    }
                }else{
                    DB::table('students')->where('id', $this->student_enrolment_record['owner_id'])->update(['level_id'=>$this->to,'promote_count'=> $this->student->promote_count+1]);
                    DB::table('student_enrollments')->upsert($this->student_enrolment_record,'token',['level_id_from', 'level_id_to','updated_at']);
                }

            });
      
    }
}
