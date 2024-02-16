<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Excel;
use Modules\Staff\Services\CourseService;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;


class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $courseService;
    private $utilities;
    public function __construct(CourseService $courseService, Utilities $utilities)
    {
        $this->courseService = $courseService;
        $this->utilities = $utilities;
    }

    public function update(Request $request){

        try{

            $request->validate([
                "course_id" => "required",
                "title" =>"required",
               /*  "code" =>"required",
                "credit_unit" =>"required",
                "department_id" => "required",
                "level_id" => "required"  */
            ]);

            $response = $this->courseService->updateCourse($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function create(Request $request){

        try{

            $request->validate([
                "title" =>"required",
                "code" =>"required",
                "credit_unit" =>"required",
                "department_id" => "required",
                "level_id" => "required"
            ]);

            $response = $this->courseService->newCourse($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function bulkUpload(Request $request){

        try{

            $request->validate([
                "file" => "required",
                "department_id"=>"required",
                "level_id"=>"required"
            ]);

            $response = $this->courseService->bulkCourseUpload($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function deactivate(Request $request){

        try{

            $request->validate([
                "id" => "required",
            ]);

            $response = $this->courseService->deactivateCourse($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function activate(Request $request){

        try{

            $request->validate([
                "id" => "required",
            ]);

            $response = $this->courseService->activateCourse($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getCourses(Request $request){

        try{
            $key = 'courses_'.tenant('id');
            if(Redis::get($key) && !$request->has('search')){
                $response = json_decode(Redis::get($key));
            }else{
                $response = $this->courseService->courses($request);
                Redis::set($key,json_encode($response));
                Redis::expire($key,259200);
            }
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getCoursesWithoutPaginate(Request $request){

        try{

            $response = $this->courseService->coursesWithoutPaginate($request);
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getTemplate(){
        return  $this->utilities->getFile('courseUploadTemplate.csv');
    }


    public function updateCourseCategory(Request $request){
        try{
             $request->validate([
                "id"=>"required",
                "name"=>"required",
                "short_name" => "required",
            ]);

             $response = $this->courseService->updateCourseCategory($request);
            return new APIResource($response, false, 200 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function createCourseCategory(Request $request){
        try{
             $request->validate([
                "name"=>"required",
                "short_name" => "required",
            ]);

             $response = $this->courseService->createCourseCategory($request);
            return new APIResource($response, false, 200 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function deactivateCourseCategory(Request $request){
        try{
             $request->validate([
                "id"=>"required",
            ]);

             $response = $this->courseService->deactivateCourseCategory($request->get('id'));
            return new APIResource($response, false, 200 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function activateCourseCategory(Request $request){
        try{
             $request->validate([
                "id" => "required",
            ]);

             $response = $this->courseService->activateCourseCategory($request->get('id'));
            return new APIResource($response, false, 200 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getCourseCategories(){
        try{
             $response = $this->courseService->getCourseCategories();
            return new APIResource($response, false, 200 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getCourseCategoriesWithInactive(){
        try{
             $response = $this->courseService->getCourseCategoriesWithInactive();
            return new APIResource($response, false, 200 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getCourseCategoryById(Request $request){
        try{

            $response = $this->courseService->getCourseCategoryById($request->id);
            return new APIResource($response, false, 200 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

}
