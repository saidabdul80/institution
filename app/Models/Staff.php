<?php

namespace App\Models;

use App\Traits\Searchable;
use App\Traits\Utils;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class Staff  extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes, Utils,Searchable, HasPermissions, HasRoles;

    protected $fillable = ['password', 'first_login'];
    protected $with = ['roles'];
    protected $table ="staffs";

    public function getDepartmentAttribute() {
        $department = Department::find($this->department_id);

        if(!is_null($department)){
            return "{$department->name}";
        }else{
            return '';
        }
    }

     /**
     * Get all unique permissions names as an array.
     */
    
    public function getAllPermissionsAttribute()
    {
        // Retrieve all permissions directly assigned or via roles.
        return $this->getAllPermissions()->pluck('name')->unique()->sort()->values();
    }


    public function getRolePermissionsAttribute(){                
         return $this->getPermissionsViaRoles();
    }
    
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'staff_courses');        
    }

    public function scopeSearch($query, $search)
    {
        $query->orWhere('email', 'like', "%$search%")
        ->orWhere('gender', 'like', "%$search%")
        ->orWhere('type', 'like', "%$search%")
        ->orWhere('staff_number', 'like', "%$search%")
        ->orWhere('first_name', 'like', "%$search%")
        ->orWhere('surname', 'like', "%$search")
        ->orWhere('middle_name', 'like', "%$search%")
        ->orWhereRelation('courses', function($q) use($search){
            $q->orWhere('title','like',"%$search%")
                ->orWhere('code','like',"%$search%");
        });
    }

    public function getRoleAttribute()
    {
        $rolenames =  $this->getRelation('roles')->pluck('name')->toArray();
        return $rolenames;
    }

  

    public function getIsSuperAdminAttribute() {

        $rolenames =  $this->getRelation('roles')->pluck('name')->toArray();
     
        if(in_array('super-admin', $rolenames)){
            return true;
        }
        return false;
    } 

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->middle_name} {$this->surname}";
    }

    protected $appends = ['department', 'is_super_admin', 'role_permissions', 'full_name', 'all_permissions', 'role'];

    protected static function newFactory()
    {
        //return \Modules\SecuredPanelAPI\Database\factories\StaffFactory::new();
    }

    
}
