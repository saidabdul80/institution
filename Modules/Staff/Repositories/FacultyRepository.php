<?php
namespace Modules\Staff\Repositories;

use App\Models\Faculty;
use Illuminate\Support\Facades\Http;
use Database\Seeders\Courses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function PHPUnit\Framework\isNull;

class FacultyRepository{

    private $faculty;
    public function __construct(Faculty $faculty)
    {
        $this->faculty = $faculty;
    }

    public function exists($id, $name, $abbr){

      return $this->faculty::where(function($query) use ($name, $abbr){
            $query->where(['name'=>$name,])->orWhere(['abbr'=>$abbr,]);
        })->where('id','!=',$id)->first();

    }

    public function update($request){

        $faculty = $this->faculty::find($request->get('id'));
        $faculty->name = $request->get('name');
        $faculty->abbr = $request->get('abbr');
        $faculty->save();
        return $faculty;

    }

    public function create($request){

        $faculty = $this->faculty::create(["name" => $request->get('name'),"abbr" => $request->get('abbr')]);
        return $faculty;

    }


    public function delete($id){

        $IsInUsed =  DB::table('departments')->where('faculty_id', $id)->first();
        if(!$IsInUsed){
            $faculty = $this->faculty::find($id);
            if($faculty){
                try{
                    $faculty->delete();
                    return 'success';
                }catch(\Exception $e){
                    throw new \Exception("Faculty could not be Deactivated");
                }
            }else{
                throw new \Exception('Faculty Not Found', 404);  ;
            }
        }

        throw new \Exception("Faculty could not be Deactivated");
    }

    public function unDelete($id){

        $faculty = $this->faculty::onlyTrashed()->find($id);
        if($faculty){
            $faculty->restore();
            return 'success';
        }else{
            throw new \Exception('Faculty Not Found', 404);  ;
        }

    }

    public function getData($search=null,  $paginateBy=null){   
        $paginate = $paginateBy ?? 100;
        return $this->faculty::search($search)->latest()->paginate($paginate);        
    }

    public function getDataWithoutPaginate($search=null){           
        return $this->faculty::search($search)->latest()->get();
    }

    public function names(){
       return $this->faculty::pluck("name")->toArray();
    }

    public function abbrs(){
        return $this->faculty::pluck("abbr")->toArray();
    }

    public function uploadBulkData($data){
        return $this->faculty::insert($data);
    }
}
