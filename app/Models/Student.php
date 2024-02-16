<?php

namespace App\Models;

use App\Traits\HasWallet;
use App\Traits\Searchable;
use App\Traits\Utils;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Support\Facades\DB;
use ProgrammeOptions;

class Student extends Authenticatable
{

    use HasFactory, Utils, HasApiTokens, SoftDeletes, HasWallet,Filterable;

    protected $fillable = ['*'];
    protected $with = ['statuses'];
    public static bool $withoutAppends = false;
    public function __construct()
    {
        if (self::$withoutAppends) {            
            $this->appends = [];           
        }
    }
    
    public function modelFilter()
    {
        return $this->provideFilter(\App\ModelFilters\StudentFilter::class);
    }

    protected static function newFactory()
    {
        //return \Modules\StudentPortalAPI\Database\factories\StudentFactory::new();
    }

    static public function scopeLike($query,$obj)
    {
        foreach($obj as $column => $value){
            if($value != ""){
                $query->orWhere($column,'like', $value);
            }
        }
        return $query;
    }

    public function alevel()
    {
        return $this->hasMany(Alevel::class,'applicant_id', "application_id");
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class,"owner_id", "id");
    }

    public function olevel()
    {
        return $this->hasMany(OlevelResult::class,'applicant_id', "application_id");
    }

    public function level(){
        return $this->belongsTo(Level::class);
    }

    public function programme(){
        return $this->belongsTo(Programme::class);
    }

    public function entryMode(){
        return $this->belongsTo(EntryMode::class);
    }

    public function qualifications(){
        return $this->hasMany(ApplicantQualification::class,'applicant_id', 'application_id');
    }

    public function getLevelAttribute() {
        $level = Level::find($this->level_id);
        if(!is_null($level)){
            return "{$level->title}";
        }else{
            return '';
        }
    }

    public function getModeOfEntryAttribute() {
        $entry_mode = EntryMode::find($this->mode_of_entry_id);
        if(!is_null($entry_mode)){
            return "{$entry_mode->entry_mode}";
        }else{
            return '';
        }
    }

    public function getStateAttribute() {
        $entry_mode = State::find($this->state_id);
        if(!is_null($entry_mode)){
            return "{$entry_mode->name}";
        }else{
            return '';
        }
    }

    public function getCountryAttribute() {
        $entry_mode = Country::find($this->country_id);
        if(!is_null($entry_mode)){
            return "{$entry_mode->name}";
        }else{
            return '';
        }
    }

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

    public function getYearOfGraduationAttribute() {
        $programme = Programme::find($this->programme_id);
        $session = Session::find($this->entry_session_id);
        if(!is_null($programme)){
            $sessions =  explode('/',$session?->name);
            if(sizeof($sessions)>1){
                $year = '01-01-'. $sessions[1];
                $dt = Carbon::parse($year);
                $dt->addYear($programme->duration);
                return "{$dt->format('Y')}";
            }
            return '';
        }else{
            return '';
        }
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->middle_name} {$this->surname}";
    }

    public function getProgrammeTypeNameAttribute()
    {
        return DB::table('programme_types')->where("id", $this->programme_type_id)->first()->name ?? "";
    }

    public function getProgrammeNameAttribute()
    {
        return DB::table('programmes')->where("id", $this->programme_id)->first()->name ?? "";
    }

    public function getLgaNameAttribute()
    {
        $lga = DB::table('l_g_as')->where('id', $this->lga_id)->first();
        if(!is_null($lga)){
            return $lga->name;
        }else{
            return '';
        }
    }

    public function getGraduationLevelIdAttribute()
    {
        return Programme::find($this->programme_id)?->graduation_level_id;            
    }

    public function scopeSearch($query, $search)
    {
        if(!is_null($search)){
            return $query->where('first_name', 'like', '%' . $search . '%')
                ->orWhere('middle_name', 'like', '%' . $search . '%')
                ->orWhere('surname', 'like', '%' . $search . '%')
                ->orWhere('phone_number', 'like', '%' . $search . '%')
                ->orWhere('gender', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('matric_number', 'like', '%' . $search . '%');                                
        }
    }

    public function getProgrammeMaxDurationAttribute(){
        return Programme::find($this->programme_id)?->max_duration;            
    }

    public function getNumberOfCarryOverAttribute(){
        return StudentCoursesGrades::where(['student_id'=>$this->id, 'status'=>'failed'])->count();            
    }

    public function getOcCountAttribute()
    {
        return StudentCoursesGrades::where('student_id', $this->id)->where('grade_status', 'failed')->count();
    }

    public function previous_result()
    {
        return $this->hasOne(Result::class, 'student_id', 'id');
    }

    public function latest_result()
    {
        return $this->hasOne(Result::class, 'student_id', 'id');
    }

    public function cos()
    {
        return $this->hasMany(StudentCoursesGrades::class, 'student_id', 'id');
    }

    public function student_courses_grades()
    {
        return $this->hasMany(StudentCoursesGrades::class, 'student_id', 'id');
    }

    public function result()
    {
        return $this->hasOne(Result::class, 'student_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'owner_id', 'id');
    }


    public function getTpGradeAttribute()
    {
        return StudentCoursesGrades::where('student_id', $this->id)->where('tp', 'yes')->first()->grade ?? "F";
    }

    public function getProgrammeOptionAttribute()
    {
        return ProgrammeOption::find($this->programme_option_id);   
    }
    
    public function statuses(){
        return $this->hasMany(StudentStatus::class);
    }

    public function getUserTypeAttribute(){
        return 'student';
    }

    public function getAllowTpAttribute(){
        $programme = Programme::find($this->programme_id);        
        if($this->getAttribute('oc_count') > $programme?->tp_max_carry_over){
            return false;
        }
        return true;
    }

    protected $appends = ['allow_tp', 'graduation_level_id','full_name', 'programme_name', 'programme_type_name', 'lga_name','faculty','department','mode_of_entry','level','state','country', 'year_of_graduation', 'programme_max_duration', 'programme_option','user_type'];
}
