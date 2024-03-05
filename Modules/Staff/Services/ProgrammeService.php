<?php
namespace Modules\Staff\Services;

use Modules\Staff\Repositories\ProgrammeRepository;
use Exception;
use Modules\Staff\Services\Utilities;

class ProgrammeService extends Utilities{

    private $programmeRepository;
    private $utility;
    private $user_id;
    public function __construct( ProgrammeRepository $programmeRepository, Utilities $utilities)
    {

        $this->programmeRepository = $programmeRepository;
        $this->utility = $utilities;
        //$this->user_id = auth('api-staff')->id();
    }


    public function updateProgramme($request){
        $name = $request->get('name')??"";
        $existData = $this->programmeRepository->exists($request->get('id'), $name);
        if($existData){
            throw new Exception("Programme Name Already Exist",400);
        }
        $data = $request->all();
        $this->programmeRepository->update($request->get('id'), $data);
        return 'success';

    }

    public function newProgramme($request){

        $existData = $this->programmeRepository->exists(null,$request->get('name'));
        if($existData){
            throw new Exception("Programme Name Already Exist",400);
        }

        $data = $request->all();
        try{
            $this->programmeRepository->create($data);
            return 'success';
        }catch(\Exception $e){
            throw new Exception($e->getMessage(),400);
        }


    }

    public function bulkProgrammeUpload($request){

        $file = $request->file;
        $programmes = $this->utility->fileToArray($file);
        $names = $this->programmeRepository->names();

        $nameExist = [];

        foreach ($programmes as $index => &$programme) {

            if(in_array($programme['name'], $names)){

                $nameExist["line_". $index] = $programme['name'];
                unset($programmes[$index]);
            }
            $programme["department_id"] = $request->get('department_id');
            $programme["faculty_id"] = $request->get('faculty_id');
        }

        $response = $this->programmeRepository->uploadBulkData($programmes);
        if($response){
            return ["status"=>'success', "Duplicates"=> $nameExist];
        }

        throw new Exception("Upload Failed, Try Again",400);

    }



    public function deactivateProgramme($request){

        $response =  $this->programmeRepository->delete($request->get('id'));
        return 'success';

    }

    public function activateProgramme($request){

       $response = $this->programmeRepository->unDelete($request->get('id'));
        return 'success';

    }
    public function programmes($request)
    {
       return $this->programmeRepository->getData($request);
    }

    public function assignCoursesToProgramme($request){
        $data = $request->all();
        $course_ids = $data['course_ids'];
        unset($data['course_ids']);        
        $data['staff_id'] = $request->user()->id;
        return $this->programmeRepository->assign($data, $course_ids);

    }

    public function updateProgrammeCourse($request){

        return $this->programmeRepository->updateAssignedProgrammeCourse($request->get("id"),$request->get('programme_id'),$request->get('level_id'),$request->get('semester_id'),$request->get('course_id'), $request->get('staff_id'));

    }    

    public function unAssignCoursesFromProgramme($request){

        return $this->programmeRepository->unAssign($request->get('ids'));

    }

    public function programmeCourses($request){        
        $response = $this->programmeRepository->programmeCourses($request->session_id,$request->search, $request->paginateBy);       
        return $response;
    }

    public function programmeCoursesWithoutPaginate($request){        
        $response = $this->programmeRepository->programmeCoursesWithoutPaginate($request->search);       
        return $response;
    }

    public function getProgrammeById($request){

        $response = $this->programmeRepository->getProgrammeById($request->id);       
        return $response;
    }

    
   

    
}
