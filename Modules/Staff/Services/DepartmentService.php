<?php
namespace Modules\Staff\Services;

use Modules\Staff\Repositories\DepartmentRepository;
use Exception;
use Modules\Staff\Services\Utilities;

class DepartmentService extends Utilities{

    private $departmentRepository;
    private $utility;

    public function __construct( DepartmentRepository $departmentRepository,Utilities $utilities)
    {        

        $this->departmentRepository = $departmentRepository;                
        $this->utility = $utilities;
    }


    public function updateDepartment($request){
        
        $existData = $this->departmentRepository->exists($request->get('id'), $request->get('name'), $request->get('abbr'));
        if($existData){
            throw new Exception("Department Name or Abbreviation Name Already Exist",400);               
        }                    

        $this->departmentRepository->update($request->get('id'),$request->all());                
        return 'success';         

    }

    public function newDepartment($request){
        
        $existData = $this->departmentRepository->exists($request->get('id'), $request->get('name'), $request->get('abbr'));
        if($existData){
            throw new Exception("Department Name or Abbreviation Name Already Exist",400);               
        }   

        $response = $this->departmentRepository->create($request->all());
        if($response){
            return 'success';         
        }                  

        throw new Exception("Could Not Create New Department",400);                       

    }

    public function bulkDepartmentUpload($request){
        
        $file = $request->file;
        $departments = $this->utility->fileToArray($file); 
        $names = $this->departmentRepository->names();
        $abbrs = $this->departmentRepository->abbrs();
      
        $nameExist = [];

        foreach ($departments as $index => &$department) {

            if(in_array($department['name'], $names) || in_array($department['abbr'], $abbrs)){

                $nameExist["line_". $index] = $department['name']. ' Or '. $department['abbr'];
                unset($departments[$index]);
            }

            $department['faculty_id'] = $request->get('faculty_id');
        }

        $response = $this->departmentRepository->uploadBulkData($departments);
        if($response){
            return ["status"=>'success', "Duplicates"=> $nameExist];
        }

        throw new Exception("Upload Failed, Try Again",400);            

    }

    

    public function deactivateDepartment($request){      
        
        $response =  $this->departmentRepository->delete($request->get('id'));                
        return 'success';                         

    }

    public function activateDepartment($request){
        
       $response = $this->departmentRepository->unDelete($request->get('id'));
        return 'success';         

    }
    public function departments($request)
    {
        if($request->has('withoutPaginate')){
            return $this->departmentRepository->getDataWithoutPaginate($request->search);
        }
       return $this->departmentRepository->getData($request->get('search'),$request->get('paginateBy'));
    }

    public function departmentsWithoutPaginate($request)
    {
       return $this->departmentRepository->getDataWithoutPaginate($request->search);
    }
    


}