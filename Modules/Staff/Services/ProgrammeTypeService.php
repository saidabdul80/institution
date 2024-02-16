<?php
namespace Modules\Staff\Services;

use Modules\Staff\Repositories\ProgrammeTypeRepository;
use Exception;

class ProgrammeTypeService {

    private $programmeTypeRepository;        
    public function __construct( ProgrammeTypeRepository $programmeTypeRepository)
    {        
        $this->programmeTypeRepository = $programmeTypeRepository;                        
    }


    public function create($request){        
        return $this->programmeTypeRepository->create($request->get('name'),$request->get('short_name'));                           
    }

    public function update($request){        
        return $this->programmeTypeRepository->update($request->get('id'),$request->get('name'),$request->get('short_name'));                           
    }

    public function delete($request){        
        return $this->programmeTypeRepository->delete($request->get('id'));                           
    }

    public function all($request){
        return $this->programmeTypeRepository->fetch($request->search, $request->paginateBy);
    }

}