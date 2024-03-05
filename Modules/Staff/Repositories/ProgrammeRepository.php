<?php
namespace Modules\Staff\Repositories;

use App\Models\Programme;
use App\Models\ProgrammeCourses;
use Illuminate\Support\Facades\Http;
use Database\Seeders\Courses;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProgrammeRepository{

    private $programme;
    private $programmeCourse;
    public function __construct(Programme $programme, ProgrammeCourses $programmeCourse)
    {
        $this->programme = $programme;
        $this->programmeCourse = $programmeCourse;
    }

    public function exists($id, $name){
        if(is_null($id)){
            return $this->programme::where('name', $name)->first();
        }
        return $this->programme::where('name', $name)->where('id','!=',$id)->first();


    }

    public function update($id, $data){

        $programme = $this->programme::find($id);
        foreach($this->programme->appends_props as  $appends){
            if(isset($data[$appends])){
                unset($data[$appends]);
            }
        }        
        foreach($data as $key => $value){
            if($key != "id"){
                $programme->$key = $value;
            }
        }

        $programme->save();
        return $programme;

    }

    public function create($data){
        foreach($this->programme->appends_props as  $appends){
            if(isset($data[$appends])){
                unset($data[$appends]);
            }
            if(isset($data['id'])){
                unset($data['id']);
            }
        }
        $data['required_subjects'] = implode(',',$data['required_subjects']);
        $programme = $this->programme::insert($data);
        return $programme;

    }


    public function delete($id){

        $IsInUsed = $this->checkInstanceExist('programmes', 'programme_id', $id);
        if (!$IsInUsed) {
            $programme = $this->programme::find($id);

            if($programme){
                try{
                    return $programme->delete();
                }catch(\Exception $e){
                    throw new \Exception("programme could not be Deactivated");
                }
            }else{
                throw new \Exception('programme Not Found', 404);  ;
            }
        }
        throw new \Exception("programme could not be Deactivated");
    }

    private function checkInstanceExist($tablename, $foreignId, $id)
    {
        $sql = "SELECT DISTINCT f.id
                    FROM $tablename f LEFT JOIN programme_courses a ON a.$foreignId = f.id LEFT JOIN students s ON s.$foreignId = f.id
                    LEFT JOIN applicants d ON d.applied_programme_id = f.id WHERE
                    (CASE WHEN  a.$foreignId IS NULL
                        THEN
                        (CASE WHEN s.$foreignId IS NULL
                            THEN
                                (CASE WHEN d.applied_programme_id IS NULL
                                    THEN 0
                                    ELSE 1
                                    END
                                )
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

        $programme = $this->programme::onlyTrashed()->with('programme_option')->find($id);
        if($programme){
            $programme->restore();
        }else{
            throw new \Exception('programme Not Found', 404);  ;
        }

    }

    public function getData($request){
        $paginate = $request->paginateBy??7;
        return $this->programme::search($request->keyword)->orderBy('id','desc')->get();
    }

    public function names(){
       return $this->programme::pluck("name")->toArray();
    }

    public function uploadBulkData($data){
        return $this->programme::insert($data);
    }

    public function assign($data, $course_ids) {
        $newData = [];
        $existingRecords = $this->programmeCourse::whereIn('course_id', $course_ids)
                                ->where([
                                    'programme_id' => $data['programme_id'],
                                    'level_id' => $data['level_id'],
                                    "tp" => $data['tp'],
                                    "special_course" => $data['special_course'],
                                    "session_id" => $data['session_id'],
                                ])
                                ->withTrashed()
                                ->get()
                                ->keyBy('course_id');
    
        foreach ($course_ids as $course_id) {
            $record = $data;
            $record['course_id'] = $course_id;
            $record['created_by'] = $data['staff_id'];
            unset($record['staff_id'], $record['deleted_at']);
    
            if (isset($existingRecords[$course_id])) {
                $existingRecord = $existingRecords[$course_id];
                // Perform update if necessary, or mark for bulk update.
                // Eloquent does not support bulk update directly, so consider a custom query or updating individual records as needed.
                $existingRecord->update($record);
                $existingRecord->deleted_at = null;
                $existingRecord->save();
            } else {
                $newData[] = $record;
            }
        }
    
        if (!empty($newData)) {
            $this->programmeCourse::insert($newData);
        }
    
        return "success";
    }
    

    public function updateAssignedProgrammeCourse($id,$programme_id,$level_id,$semester_id, $course_id,$user_id){
        $this->programmeCourse::where("id", $id)->update(['programme_id'=>$programme_id, 'course_id'=>$course_id, 'level_id'=>$level_id, 'semester_id'=>$semester_id,"updated_by"=>$user_id]);
        return "success";
    }

    public function unAssign($ids){
        return $this->programmeCourse::whereIn('id',$ids)->delete();
         /* $unUsed = $this->checkInstanceExist2('courses', 'course_id', $ids);
        if (sizeof($unUsed)>0) {
        }else{
            if(sizeof($unUsed)> 0 && (sizeof($ids)> sizeof($unUsed) || sizeof($ids)< sizeof($unUsed) )){
                throw new \Exception("some of the courses could not be deassigned");
            }
            throw new \Exception("could not deassign courses");
        } */
    }

    private function checkInstanceExist2($tablename, $foreignId, $ids)
    {
        $sql = "SELECT DISTINCT f.id
                    FROM $tablename f LEFT JOIN programme_courses a ON a.$foreignId = f.id
                    WHERE
                    (CASE WHEN  a.$foreignId IS NULL
                        THEN 0
                        ELSE 1
                    END)";
       $response = DB::select(DB::raw($sql));  //all model instances id used in another model
       $response = array_column($response,'id');
       $unUsed  = array_filter($ids, function($id) use ($response){
            if(!in_array($id, $response)){
                return $id;
            }
       });
       return $unUsed;
    }

    public function programmeCourses($session_id = null, $search = null, $paginateBy = null)
    {
        $query = Programme::with(['courses' => function($query) use ($session_id) {
            // Apply session_id filter to courses if provided
            if (!empty($session_id)) {
                $query->where('session_id', $session_id);
            }
        }]);

        // Apply search criteria if provided
        if (!empty($search)) {
            // Assuming you have a search scope or method on the Programme model
            $query->search($search);
        }

        // Apply latest ordering
        $query->latest();

        // Paginate results
        //$paginate = $paginateBy ?? 100;
        $programmes = $query->get();

        // Append 'courses_codes' attribute to each Programme model in the collection
        $programmes->each(function($programme) {
            $programme->append('courses_codes');
        });

        return $programmes;
    }


    public function programmeCoursesWithoutPaginate($search){
        return $this->programmeCourse::search($search)->latest()->get();
    }

    static public function getProgrammeById($id)
    {
        return Programme::with('programme_options')->where('id',$id)->first();
    }
}

