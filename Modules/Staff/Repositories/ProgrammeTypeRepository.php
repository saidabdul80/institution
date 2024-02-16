<?php
namespace Modules\Staff\Repositories;

use App\Events\ProgrammeTypeCreated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\ProgrammeType;
use Exception;

class ProgrammeTypeRepository{


    private $programmeType;        
      
    public function __construct(ProgrammeType $programmeType)
    {        
        $this->programmeType = $programmeType;                
    }

    public function exists($name,$shortname){
        return $this->programmeType::where('name', $name)->orWhere('short_name',$shortname)->exists();

    }

    public function create($name, $shortname)
    {        
        if(!$this->programmeType::where(["name"=> $name,"short_name"=> $shortname])->exists()){            
                $programmeType = $this->programmeType::create([
                    "name"=> $name,
                    "short_name"=> $shortname
                ]);          
                event(new ProgrammeTypeCreated($programmeType));
            return 'Created Successfully';
        }
        return throw new Exception('Already Exist', 400);
    }

    public function Update($id, $name, $shortname)
    {                
         $this->programmeType::where('id',$id)->update(["name"=> $name,"short_name"=> $shortname]);        
         return 'Updated Successfully';
    }

    public function delete($id)
    {        
        $IsInUsed_by_student =  DB::table('students')->where('programme_type_id', $id)->first();
        $IsInUsed_by_applicant =  DB::table('applicants')->where('programme_type_id', $id)->first();
        if(empty($IsInUsed_by_student) && empty($IsInUsed_by_applicant)){
            $programmeType = $this->programmeType::find($id);        
            if($programmeType){
                try{                
                    $programmeType->delete();                
                    return 'success';
                }catch(\Exception $e){
                    throw new \Exception("Programme Type could not be Deactivated");
                }
            }else{
                throw new \Exception('Programme Type Not Found', 404);  ;
            }        
        }
                
        throw new \Exception("Programme Type could not be Deactivated");       
    }

    public function fetch($search, $paginateBy){
        $paginate = $paginateBy??30;
        return $this->programmeType::search($search)->latest()->paginate($paginate);
    }
}