<?php
namespace Modules\Staff\Services;
use App\Models\StudentPortalSubMenu;
use App\Models\StudentPortalMenu;
use App\Models\SummerPortalSubMenu;
use App\Models\SummerPortalMenu;
use App\Models\ApplicantsPortalMenu;
use App\Models\ApplicantsPortalSubMenu;
use Modules\Staff\Repositories\MenuRepository;
use Exception;

class MenuService {

    private $menuRepository;   
    private $summerPortalSubMenu;        
    private $summerPortalMenu;        
    private $studentPortalMenu;        
    private $studentPortalSubMenu;      
    private $applicantsPortalMenu;  
    private $applicantsPortalSubMenu;   
       
    public function __construct( 
        MenuRepository $menuRepository, 
        StudentPortalMenu $studentPortalMenu,
        StudentPortalSubMenu $studentPortalSubMenu,
        SummerPortalSubMenu $summerPortalSubMenu,
        SummerPortalMenu $summerPortalMenu,
        ApplicantsPortalMenu $applicantsPortalMenu,
        ApplicantsPortalSubMenu $applicantsPortalSubMenu

        )
    {        
        $this->menuRepository = $menuRepository;     
        $this->summerPortalSubMenu = $summerPortalSubMenu;                
        $this->summerPortalMenu = $summerPortalMenu;
        $this->studentPortalMenu  = $studentPortalMenu;
        $this->studentPortalSubMenu = $studentPortalSubMenu;                   
        $this->applicantsPortalMenu = $applicantsPortalMenu;
        $this->applicantsPortalSubMenu = $applicantsPortalSubMenu;
    }


    /**
     * Summer Portal  Menu CRUD
     */

    public function updateSummerPortalMenu($request){     
        $data = $request->all();   
        $id = $data['id'];
        unset($data['id']);
        return $this->menuRepository->update($id, $data, $this->summerPortalMenu);                           
    }

    public function allSummerPortalMenus(){
        return $this->menuRepository->fetch($this->summerPortalMenu);
    }

    /**
     * Summer Portal Sub Menu CRUD
     */

    public function updateSummerPortalSubMenu($request){     
        $data = $request->all();   
        $id = $data['id'];
        unset($data['id']);
        return $this->menuRepository->update($id, $data, $this->summerPortalSubMenu);                           
    }

    public function allSummerPortalSubMenus(){
        return $this->menuRepository->fetch($this->summerPortalSubMenu);
    }

    /**
     * Student Portal  Menu CRUD
     */

    public function updateStudentPortalMenu($request){     
        $data = $request->all();   
        $id = $data['id'];
        unset($data['id']);
        return $this->menuRepository->update($id, $data, $this->studentPortalMenu);                           
    }

    public function allStudentPortalMenus(){
        return $this->menuRepository->fetch($this->studentPortalMenu);
    }

    /**
     * Student Portal Sub Menu CRUD
     */

    public function updateStudentPortalSubMenu($request){     
        $data = $request->all();   
        $id = $data['id'];
        unset($data['id']);
        return $this->menuRepository->update($id, $data, $this->studentPortalSubMenu);                           
    }

    public function allStudentPortalSubMenus(){
        return $this->menuRepository->fetch($this->studentPortalSubMenu);
    }

    /**
     * Applicants Portal  Menu CRUD
     */

    public function updateApplicantsPortalMenu($request){     
        $data = $request->all();   
        $id = $data['id'];
        unset($data['id']);
        return $this->menuRepository->update($id, $data, $this->applicantsPortalMenu);                           
    }

    public function allApplicantsPortalMenus(){
        return $this->menuRepository->fetch($this->applicantsPortalMenu);
    }

    /**
     * Applicants Portal Sub Menu CRUD
     */

    public function updateApplicantsPortalSubMenu($request){     
        $data = $request->all();   
        $id = $data['id'];
        unset($data['id']);
        return $this->menuRepository->update($id, $data, $this->applicantsPortalSubMenu);                           
    }

    public function allApplicantsPortalSubMenus(){
        return $this->menuRepository->fetch($this->applicantsPortalSubMenu);
    }

    public function createStudentPortalMenu($request){
        return $this->menuRepository->create($request->all(), $this->studentPortalMenu);
    }

    public function createStudentPortalSubMenu($request){
        return $this->menuRepository->create($request->all(), $this->studentPortalSubMenu);
    }

    public function createApplicantPortalMenu($request){
        return $this->menuRepository->create($request->all(), $this->applicantsPortalMenu);
    }
    
    public function createApplicantPortalSubMenu($request){
        return $this->menuRepository->create($request->all(), $this->applicantsPortalSubMenu);
    }

    public function createSummerPortalMenu($request){
        return $this->menuRepository->create($request->all(), $this->summerPortalMenu);
    }

    public function createSummerPortalSubMenu($request){
        return $this->menuRepository->create($request->all(), $this->summerPortalSubMenu);
    }
    
    public function deleteApplicantPortalMenu($request){
        return $this->menuRepository->delete($request->get('id'), $this->applicantsPortalMenu);
    }

    public function deleteApplicantPortalSubMenu($request){
        return $this->menuRepository->delete($request->get('id'), $this->applicantsPortalSubMenu);
    }
    public function deleteStudentPortalMenu($request){
        return $this->menuRepository->delete($request->get('id'), $this->studentPortalMenu);
    }

    public function deleteStudentPortalSubMenu($request){
        return $this->menuRepository->delete($request->get('id'), $this->studentPortalSubMenu);
    }

    public function deleteSummerPortalMenu($request){
        return $this->menuRepository->delete($request->get('id'), $this->summerPortalMenu);
    }

    public function deleteSummerPortalSubMenu($request){
        return $this->menuRepository->delete($request->get('id'), $this->summerPortalSubMenu);
    }
    
}