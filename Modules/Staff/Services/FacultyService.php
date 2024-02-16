<?php
namespace Modules\Staff\Services;

use Modules\Staff\Repositories\FacultyRepository;
use Exception;
use Modules\Staff\Services\Utilities;

class FacultyService extends Utilities{

    private $facultyRepository;
    private $utility;

    public function __construct( FacultyRepository $facultyRepository, Utilities $utilities)
    {        

        $this->facultyRepository = $facultyRepository;                
        $this->utility = $utilities;
    }


    public function updateFaculty($request){
        
        $existData = $this->facultyRepository->exists($request->get('id'), $request->get('name'), $request->get('abbr'));
        if($existData){
            throw new Exception("Faculty Name or Abbreviation Name Already Exist",400);               
        }                    

        $this->facultyRepository->update($request);                
        return 'success';         

    }

    public function newFaculty($request){
        
        $existData = $this->facultyRepository->exists($request->get('id'), $request->get('name'), $request->get('abbr'));
        if($existData){
            throw new Exception("Faculty Name or Abbreviation Name Already Exist",400);               
        }   

        $response = $this->facultyRepository->create($request);
        if($response){
            return 'success';         
        }                  

        throw new Exception("Could Not Create New Faculty",400);                       

    }
    
    public function bulkFacultyUpload($request){
        
        $file = $request->file;
        $faculties = $this->utility->fileToArray($file); 
        $names = $this->facultyRepository->names();
        $abbrs = $this->facultyRepository->abbrs();
      
        $nameExist = [];

        foreach ($faculties as $index => $faculty) {

            if(in_array($faculty['name'], $names) || in_array($faculty['abbr'], $abbrs)){

                $nameExist["line_". $index] = $faculty['name']. ' Or '. $faculty['abbr'];
                unset($faculties[$index]);

            }
            
        }

        $response = $this->facultyRepository->uploadBulkData($faculties);
        if($response){
            return ["status"=>'success', "Duplicates"=> $nameExist];
        }

        throw new Exception("Upload Failed, Try Again",400);            

    }

    

    public function deactivateFaculty($request){      
        
        $response =  $this->facultyRepository->delete($request->get('id'));                
        return 'success';                         

    }

    public function activateFaculty($request){
        
       $response = $this->facultyRepository->unDelete($request->get('id'));
        return 'success';         

    }
    public function faculties($request)
    {
       return $this->facultyRepository->getData($request->search, $request->paginateBy);
    }

    public function facultiesWithoutPaginate($request)
    {
       return $this->facultyRepository->getDataWithoutPaginate($request->search, $request->paginateBy);
    }

}