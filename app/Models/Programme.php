<?php

namespace App\Models;

use App\Casts\CommaSeparatedCast;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ProgrammeOptions;

class Programme extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $with = ['programme_options'];
    protected $casts =[
        'required_subjects' => CommaSeparatedCast::class,
    ];
    protected $fillable = ['*'];
    public function getFacultyAttribute() {
        $faculty = Faculty::find($this->faculty_id);
        if(!is_null($faculty)){
            return "{$faculty->name}";
        }else{
            return '';
        }
    }

    public function getDepartmentAttribute() {
        $department = Department::find($this->department_id);
        if(!is_null($department)){
            return "{$department->name}";
        }else{
            return '';
        }
    }
    
    public function getCoursesCodesAttribute()
    {
        return $this->courses->pluck('code')->join(', ');
    }

    public function getEntryModeAttribute() {
        $entryMode = EntryMode::find($this->entry_mode_id);
        if(!is_null($entryMode)){
            return "{$entryMode->title}";
        }else{
            return '';
        }
    }

    public function getProgrammeTypeAttribute() {
        $programme_type = ProgrammeType::find($this->programme_type_id);
        if(!is_null($programme_type)){
            return "{$programme_type->name}";
        }else{
            return '';
        }
    }
    
    public function getSubjectsAttribute() {
        $obj = Subject::whereIn('id',$this->required_subjects)->pluck('name');
        return $obj;
    }

    public function getGraduationLevelAttribute() {
        $obj = Level::find($this->graduation_level_id);
        if(!is_null($obj)){
            return "{$obj->title}";
        }else{
            return '';
        }
    }


    public function scopeSearch($query, $search)
    {
        if(!is_null($search)){
            return $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%');
        }
    }

    public function programme_options(){
        return $this->hasMany(ProgrammeOption::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'programme_courses', 'programme_id', 'course_id')
                    ->withPivot('id as programme_course_id')->wherePivot('deleted_at',null);
    }
    

    protected $appends = ['entry_mode','department','faculty','programme_type','graduation_level','subjects'];
    public $appends_props = ['entry_mode','department','faculty','programme_type','graduation_level','subjects','programme_options'];
}
