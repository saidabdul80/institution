<?php
namespace Modules\Staff\Services;

use App\Models\Session;
use Modules\Staff\Repositories\SessionRepository;
use Exception;
use Modules\Staff\Repositories\StaffRepositories;
use Modules\Staff\Services\Utilities;

class SessionService extends Utilities{

    private $sessionRepository;
    private $utilities;
    private $user;
    private $staffRepository;
    private $session;
    public function __construct( SessionRepository $sessionRepository, StaffRepositories $staffRepository, Session $session, Utilities $utilities)
    {        

        $this->sessionRepository = $sessionRepository;                
        $this->utilities = $utilities;        
        $this->staffRepository = $staffRepository;
        $this->$session = $session;
    }


    public function create($request){        
        if(!$this->sessionRepository->exists($request->get('name'))){
            $this->sessionRepository->create($request->get('name'));                   
            return 'success';
        }
        throw new \Exception('Session already exists', 404);
    }

    public function update($request){        
        if(!$this->sessionRepository->existsInOthers($request->get('id'),$request->get('name'))){
            $this->sessionRepository->update($request->get('id'),$request->get('name'));                   
            return 'success';
        }
        throw new \Exception('Session already exists', 404);
    }

    public function sessions(){
        return $this->sessionRepository->fetch();
    }

    
    public function createSession($request){        
        return $this->sessionRepository->create($request->name);
    }
    
    public function updateSession($request){        
        return $this->sessionRepository->update($request->id, $request->name);
    }

    public function deleteSession($request){
        return $this->sessionRepository->deleteSession($request->id);
    }
}