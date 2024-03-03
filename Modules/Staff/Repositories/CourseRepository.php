<?php
namespace Modules\Staff\Repositories;

use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Support\Facades\Http;
use Database\Seeders\Courses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CourseRepository{

    private $course;
    private $courseCategory;
    public function __construct(Course $course, CourseCategory $courseCategory)
    {
        $this->course = $course;
        $this->courseCategory = $courseCategory;
    }

    public function exists($id, $code){
        return $this->course::where('code', $code)->where('id','!=',$id)->first();


    }

    public function update($id, $data){

        $course = $this->course::find($id);
        foreach($data as $key => $value){
            if($key != "id"){
                $course->$key = $value;
            }
        }

        $course->save();
        return $course;

    }

    public function create($data){
        $course = $this->course::create($data);
        return $course;
    }


    public function delete($id){

        $IsInUsed = $this->checkInstanceExist('courses', 'course_id', $id);
        if (!$IsInUsed) {
            $course = $this->course::find($id);
            if($course){
                try{
                    return $course->delete();
                }catch(\Exception $e){
                    throw new \Exception("course could not be Deactivated");
                }
            }else{
                throw new \Exception('course Not Found', 404);  ;
            }
        }
        throw new \Exception("course could not be Deactivated");

    }

    private function checkInstanceExist($tablename, $foreignId, $id)
    {
        $sql = "SELECT DISTINCT f.id
                    FROM $tablename f LEFT JOIN programme_courses a ON a.$foreignId = f.id LEFT JOIN staff_courses s ON s.$foreignId = f.id
                    WHERE
                    (CASE WHEN  a.$foreignId IS NULL
                        THEN
                        (CASE WHEN s.$foreignId IS NULL
                            THEN 0
                            ELSE 1
                            END
                        )
                        ELSE 1
                    END)";

       $response = DB::select(DB::raw($sql));  //all model instances id used in another model
       $response = array_column($response,'id');
       if(in_array($id,$response)){
         return true; // this id is in used
       }
       return false;
    }

    public function unDelete($id){

        $course = $this->course::onlyTrashed()->find($id);
        if($course){
            $course->restore();
        }else{
            throw new \Exception('course Not Found', 404);  ;
        }

    }

    public function getData($search=null,  $paginateBy=null){   
        $paginate = $paginateBy ?? 7;
        return $this->course::search($search)->latest()->paginate($paginate);        
    }

    public function getDataWithoutPaginate($search=null){
        return $this->course::search($search)->latest()->get();
    }

    
    public function codes(){
       return $this->course::pluck("code")->toArray();
    }

    public function uploadBulkData($data){
        return $this->course::insert($data);
    }

    public function updateCourseCategory($id, $data){
        $this->courseCategory::where('id', $id)->update($data);
        return 'success';
    }
    public function createCourseCategory($data){                
        return $this->courseCategory::create($data);
        return 'success';
    }
    public function deactivateCourseCategory($id){
        $this->courseCategory::where("id",$id)->update(['status'=>'Inactive']);
        return 'success';
    }

    public function activateCourseCategory($id){
        $this->courseCategory::where("id",$id)->update(['status'=>'Active']);
        return 'success';
    }
    
    public function getCourseCategories(){
        return $this->courseCategory::where("status","Active")->paginate(7);
    }

    public function getCourseCategoriesWithInactive(){
        return $this->courseCategory::all();
    }

    public function courseCategoryById($id){
        return $this->courseCategory::where('id',$id)->first();
    }
}
