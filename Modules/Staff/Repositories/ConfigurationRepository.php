<?php
namespace Modules\Staff\Repositories;



use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Configuration;

class ConfigurationRepository{


    private $configuration;        
      
    public function __construct(Configuration $configuration)
    {        
        $this->configuration = $configuration;                
    }


    public function update($name, $data)
    {            
        if($name == 'current_session'){         
            $this->createNewInvoiceTypes($name,$data["value"]);   
            $this->createNewProgrammeCourses($name,$data["value"]);
        }                
        $this->configuration::where('name',$name)->update($data);           
    }
    
    public function createNewInvoiceTypes($name,$value){
        $current_session = DB::table('configurations')->where('name',$name)->first()->value;
        $invoicetypes = DB::table('invoice_types')->where('session_id',$current_session)->get()->toArray();
        
        $invoicetypesExist = DB::table('invoice_types')->where('session_id',$value)->exists();
        if(!$invoicetypesExist){
            foreach($invoicetypes as &$invoicetype){
                unset($invoicetype->id);
                $invoicetype->session_id = $value;
                $invoicetype = (array) $invoicetype;
            }
            
            DB::table('invoice_types')->insert($invoicetypes);
        }
    }

    public function createNewProgrammeCourses($name,$value){
        $current_session = DB::table('configurations')->where('name',$name)->first()->value;
        $programmeCourses = DB::table('programme_courses')->where('session_id',$current_session)->get()->toArray();
        
        $programmeCoursesExist = DB::table('programme_courses')->where('session_id',$value)->exists();
        if(!$programmeCoursesExist){
            foreach($programmeCourses as &$programmeCourse){
                unset($programmeCourse->id);
                $programmeCourse->session_id = $value;
                $programmeCourse = (array) $programmeCourse;
            }                                
            DB::table('programme_courses')->insert($programmeCourses);
        }
    }

    static public function fetch($name)
    {
        return Configuration::where('name',$name)->first();
    }

    public function fetchAll(){
        return $this->configuration::get();
    }
}