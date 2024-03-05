<?php
namespace Modules\Staff\Services;

use Modules\Staff\Repositories\CourseRepository;
use Exception;
use Modules\Staff\Services\Utilities;

class CourseService extends Utilities{

    private $courseRepository;
    private $utility;
    private $user_id;
    public function __construct( CourseRepository $courseRepository, Utilities $utilities)
    {

        $this->courseRepository = $courseRepository;
        $this->utility = $utilities;
    }


    public function updateCourse($request){
//        $code = $request->get('code')??"";
//        $existData = $this->courseRepository->exists($request->get('course_id'), $code);
//        if($existData){
//            throw new Exception("Course Name or Abbreviation Name Already Exist",400);
//        }
        $data = $request->all();
        $data['updated_by'] = auth('api-staff')->id();

        $this->courseRepository->update($request->get('course_id'),[
            'code' => $request->get('code'),
            'title' => $request->get('title'),
            'department_id' => $data['department_id'],
            'level_id' => $data['level_id'],
            'credit_unit' => $data['credit_unit']
        ]);
        return 'success';

    }

    public function newCourse($request){

        $existData = $this->courseRepository->exists($request->get('id'), $request->get('code'));
        if($existData){
            throw new Exception("Course Code Already Exist",400);
        }
        $data = $request->all();
        $data['created_by'] = auth('api-staff')->id();
        $response = $this->courseRepository->create($data);
        if($response){
            return 'success';
        }

        throw new Exception("Could Not Create New Course",400);

    }

    public function bulkCourseUpload($request){

        $file = $request->file;
        $courses = $this->utility->fileToArray($file);
        $codes = $this->courseRepository->codes();

        $nameExist = [];

        foreach ($courses as $index => &$course) {

            if(in_array($course['code'], $codes)){

                $nameExist["line_". $index] = $course['code'];
                unset($courses[$index]);
            }else{

                $course['department_id'] =$request->get('department_id');
                $course["created_by"] = auth('api-staff')->id();
            }

        }

        $response = $this->courseRepository->uploadBulkData($courses);
        if($response){
            return ["status"=>'success', "Duplicates"=> $nameExist];
        }

        throw new Exception("Upload Failed, Try Again",400);

    }



    public function deactivateCourse($request){

        $response =  $this->courseRepository->delete($request->get('id'));
        return 'success';

    }

    public function activateCourse($request){

       $response = $this->courseRepository->unDelete($request->get('id'));
        return 'success';

    }
    public function courses($request)
    {        
       return $this->courseRepository->getData($request->get('search'),$request->get('paginate'));
    }

    public function coursesWithoutPaginate($request)
    {
       return $this->courseRepository->getDataWithoutPaginate($request->search);
    }

    public function updateCourseCategory($request){
        $id = $request->get('id');
        $data = $request->all();
        unset($data['id']);
        return $this->courseRepository->updateCourseCategory($id, $data);
    }

    public function createCourseCategory($request){
        return $this->courseRepository->createCourseCategory($request->all());
    }

    public function deactivateCourseCategory($id){
        return $this->courseRepository->deactivateCourseCategory($id);
    }

    public function activateCourseCategory($id){
        return $this->courseRepository->activateCourseCategory($id);
    }

    public function getCourseCategories(){
        return $this->courseRepository->getCourseCategories();
    }

    public function getCourseCategoriesWithInactive(){
        return $this->courseRepository->getCourseCategoriesWithInactive();
    }

    public function getCourseCategoryById($id){
        return $this->courseRepository->courseCategoryById($id);
    }


}
