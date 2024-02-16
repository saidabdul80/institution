<?php
namespace Modules\Staff\Repositories;



use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Staff\Entities\Session;

class SessionRepository{


    private $session;        
      
    public function __construct(Session $session)
    {        
        $this->session = $session;                
    }

    public function exists($name){
        return $this->session::where('name', $name)->exists();

    }

    public function create($name)
    {
        return $this->session::insert([
            "name"=> $name
        ]);           
    }

    public function update($id, $name)
    {
        return $this->session::where('id',$id)->update(["name"=> $name]);           
    }

    public function existsInOthers($id, $name){
        return $this->session::where(['name'=> $name])->whereNotIn('id', [$id])->exists();
    }

    public function fetch(){
        return $this->session::all();
    }

    
    public function deleteSession($id){
        $applicant = DB::table('applicants')->where('session_id', $id)->first();
        $student = DB::table('students')->where('entry_session_id', $id)->first();
        $staff = DB::table('invoice_types')->where('session_id', $id)->first();
        if(!empty($applicant) || !empty($student) || !empty($staff)){
            throw new \Exception("session cannot be deleted, already in use");
        }

        return $this->session::find($id)->delete();
    }
}
