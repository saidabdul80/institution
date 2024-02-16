<?php
namespace Modules\Staff\Services;

use Modules\Staff\Repositories\ConfigurationRepository;
use Exception;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ConfigurationService {

    private $configurationRepository;
    // private $user;
    private $role;
    private $permission;
    private $staff;
    public function __construct( ConfigurationRepository $configurationRepository, Role $role, Permission $permission, Staff $staff)
    {

        $this->configurationRepository = $configurationRepository;

        // $this->user = auth('api:staffportal')->user();

        $this->role = $role;

        $this->permission = $permission;

        $this->staff = $staff;
    }


    public function save($data){      
        $allowed_keys = array(
            "value" ,
            "programme_type_id"
        );
        // Filter the input data to only include allowed keys
        $cleaned_data = array_intersect_key($data,array_flip($allowed_keys));                                   
        $this->configurationRepository->update($data["name"],$cleaned_data);
        return 'success';
    }

    public function configurations(){
        return $this->configurationRepository->fetchAll();
    } 
    
    public function configuration($name){
        return $this->configurationRepository->fetch($name);
    } 
    
    public function givePermission($request){
        $staff_id = $request->get('staff_id'); // staff to be assign permission
        $permissions = $request->get('permissions');
        $staff =  $this->staff->find($staff_id);

        if(empty($staff)){
            throw new \Exception("Staff not found", 404);
        }
        
        $staff->givePermissionTo($permissions);
        return 'success';
    }

    public function revokePermission($request){
        $staff_id = $request->get('staff_id'); // staff to be assign permission
        $permissions = $request->get('permissions');
        $staff =  $this->staff->find($staff_id);
       
        if(is_null($staff)){
            throw new \Exception("Staff not found", 404);
        }

        $staff->revokePermissionTo($permissions);
        return 'success';
    }

    public function getAllPermissions(){
        return $this->permission::all();
    }

    public function getStaffPermissions($id){
        $staff =  $this->staff->find($id);
        
        if(is_null($staff)){
            throw new \Exception("Staff not found", 404);
        }

        return $staff->getAllPermissions();
    }

    public function createRole($role_name, $permission_ids){
        if(!$this->role::where('name', $role_name)->exists()){
            $permissions = $this->permission::whereIn('id',$permission_ids)->get();
            $this->role::create(['name' => $role_name, 'guard_name' =>  "api:staffportal"])->givePermissionTo($permissions);        
            return 'success';
        }
        throw new \Exception('Role name already exist');
    }

   public function getStaffRoles($id){
        $staff =  $this->staff->find($id);
        
        if(is_null($staff)){
            throw new \Exception("Staff not found", 404);
        }
        return $staff->getAllPermissions();
    }


    public function deleteRole($id){
        $this->role::where('id',$id)->delete();
        return 'success';
    }

    public function updateRole($id, $role_name, $permission_ids){

        if($permission_ids == null){
            $role = $this->role::find($id);
            $role->name = $role_name;
            return   $role->save();
        }

        $permissions = $this->permission::whereIn('id',$permission_ids)->get();
        
        if(is_null($permissions)){
            throw new \Exception("Permissions not found", 404);
        }
        
        DB::transaction(function()use ($id, $role_name,$permissions) {            
            $role = $this->role::find($id);
            $role->name = $role_name;
            $role->save();
            $role->syncPermissions($permissions);
        });
        return 'success';        
    }

    public function addPermission($role_id,$permissionsToAdd){
        $role = $this->role::find($role_id);

        if(is_null($role)){
            throw new \Exception("Role not found", 404);
        }
        
        $permissions = collect($permissionsToAdd)->map(function ($permission) {
            return ['name' => $permission, 'guard_name' =>  "api:staffportal"];
        });
        $role->givePermissionTo($permissions);
        return 'success';
    }


    public function removePermission($role_id,$permissionsToAdd){
        $role = $this->role::find($role_id);
        
        if(is_null($role)){
            throw new \Exception("Role not found", 404);
        }
        
        $permissions = collect($permissionsToAdd)->map(function ($permission) {
            return ['name' => $permission, 'guard_name' =>  "api:staffportal"];
        });
        $role->revokePermissionTo($permissions);
        return 'success';
    }

    public function getRolePermissions($role_id){
        $role = $this->role::find($role_id);
        
        if(is_null($role)){
            throw new \Exception("Role not found", 404);
        }
        
        return $role->permissions;
    }
    public function roles(){
        return $this->role::with('permissions','users')->get();
    }

    public function rolesOfOffices(){
        return $this->role::with('permissions','users')->where('office', 'true')->get();
    }

    public function removeRoleFromStaff($role_name, $staff_id){
        $staff = $this->staff::find($staff_id);
        
        if(is_null($staff)){
            throw new \Exception("Staff not found", 404);
        }
        
        $staff->removeRole($role_name);
        return 'success';
    }


    public function assignRoleToStaff($role_name, $staff_id){
        $staff = $this->staff::find($staff_id);
        
        if(is_null($staff)){
            throw new \Exception("Staff not found", 404);
        }

        $staff->assignRole($role_name);
        return 'success';
    }
}
