<?php

namespace App\Models;

use App\Traits\HasWallet;
use App\Traits\Utils;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use EloquentFilter\Filterable;

class Applicant extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes, Utils, HasWallet, Filterable;


    protected $fillable = ['*'];
    protected $hidden =['password'];
    /*  protected static function newFactory()
    {

        return \Database\factories\ApplicantFactory::new();
    } */

    public function modelFilter()
    {
        return $this->provideFilter(\App\ModelFilters\ApplicantFilter::class);
    }

    public function alevel()
    {
        return $this->hasMany(Alevel::class);
    }

    public function olevel()
    {
        return $this->hasMany(OlevelResult::class);
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

    public function programme(){
        return $this->hasOne(Programme::class);
    }

    public function level(){
        return $this->hasOne(Level::class);
    }

    public function entryMode(){
        return $this->hasOne(EntryMode::class);
    }

    public function applicationType(){
        return $this->hasOne(ApplicationType::class);
    }

    public function applicationStatus(){
        return $this->hasOne(ApplicationStatus::class);
    }

    public function qualifications(){
        return $this->hasMany(ApplicantQualification::class,'applicant_id');
    }

    public function getQualificationAttribute()
    {
        return Qualification::find($this->prev_qualification_id)?->name;
    }

    public function getProgrammeNameAttribute() {
        return Programme::find($this->applied_programme_id)?->name;
    }

    public function getAdmittedProgrammeNameAttribute() {
        return Programme::find($this->programme_id)?->name;
    }

    public function getProgrammeTypeAttribute() {
        return ProgrammeType::find($this->programme_type_id)?->name;
    }

    public function getLevelAttribute() {
        return Level::find($this->applied_level_id)?->title;
    }

    public function getEntryModeAttribute() {
       return EntryMode::find($this->mode_of_entry_id)?->code;        
    }

    public function getApplicationTypeAttribute() {
        return ApplicationType::find($this->application_type_id)?->title;
    }

    public function getApplicationStatusAttribute() {
        return ApplicationStatus::find($this->application_status_id)?->application_status;
    }

    public function getActiveStateAttribute() {
        if($this->deleted_at != "" || $this->deleted_at != Null){
            return "not Active";
        }else{
            return "Active";
        }
    }

    public function getStateAttribute() {
        return State::find($this->state_id)?->name;
    }

    public function getCountryAttribute() {
        return Country::find($this->country_id)?->name;
    }

    public function getFacultyAttribute() {
        return Faculty::find($this->faculty_id)?->name;
    }

    public function getDepartmentAttribute() {
        return Department::find($this->department_id)?->name;
    }


    public function getLgaAttribute() {
        return LGA::find($this->lga_id)?->name;
    }

    public function getQualifyAttribute() {
        $olevelResuts = OlevelResult::where('applicant_id',$this->id)->pluck('subjects_grades');
        $programme = Programme::where('id',$this->applied_programme_id)->first();
        $subjects = !empty($programme?->required_subjects)? explode(',',$programme?->required_subjects):[];
        $grades =   !empty($programme?->accepted_grades)? collect(explode(',',$programme?->accepted_grades))->map(function($item){
                                                                    return strtolower($item);
                                                                })->toArray():[];
        $pass = 0;
        $checkSubject = [];
        $checkSubject2 = [];

        if(empty($subjects)){
            return ["is_qualify"=>true, "info" => 'no required subjects set'];
        }

        foreach($olevelResuts as $key => $olevelResut){

            //first and second result
            $oResult = collect((array) $olevelResut)->flatMap(function (array $values) {
                return array_map('strtolower', $values);
            });
            foreach($subjects as $subject){
                //the required subjects
                if($oResult->has(str_replace('"','',ucfirst($subject)))){
                    //applicant has the required subjects
                    if(in_array($oResult[$subject],$grades)){
                        //applicant pass the required subjects
                        $key==0? $checkSubject[$subject] = 1: $checkSubject2[$subject] = 1;
                    }else{
                        $key==0? $checkSubject[$subject] = 0: $checkSubject2[$subject] = 0;
                    }
                }else{
                    $key==0? $checkSubject[$subject] = 0: $checkSubject2[$subject] = 0;
                }
            }
        }

        $first  = array_sum(array_values($checkSubject));
        $second  = array_sum(array_values($checkSubject2));
        if($first == count($subjects)){
            return ["is_qualify"=>true, "info" => 'One Result'];
        }else if($first + $second >= count($subjects)){
            return ["is_qualify"=>true, "info" => 'Two Result'];
        }

        return ["is_qualify"=>false, "info" => 'Admission requirements not met'];
    }

    public function scopeSearch($query, $search)
    {
        if(!is_null($search)){
            return $query
                ->where('first_name', 'like', '%' . $search . '%')
                ->orWhere('middle_name', 'like', '%' . $search . '%')
                ->orWhere('surname', 'like', '%' . $search . '%')
                ->orWhere('phone_number', 'like', '%' . $search . '%')
                ->orWhere('gender', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('application_number', 'like', '%' . $search . '%');
        }
    }

    public function getFullNameAttribute()
    {
        return $this->first_name ?? "" . " " . $this->middle_name ?? "" . " ". $this->surname ?? "";
    }

    public function getMatricNumberAttribute()
    {
        return Student::where('application_id', $this->id)->first()?->matric_number;
    }

    public function invoices() {
        return $this->hasMany(Invoice::class,'owner_id')->where('owner_type', 'applicant');
    }

    
    public function newQuery()
    {
        $query2 = parent::newQuery();        
        $subquery = Invoice::select('status')
        ->whereRaw('owner_id= applicants.id and owner_type = "applicant" ')        
        ->whereIn('invoice_type_id', function ($query) {
            //SELECT ALL invoice type id for application fee with session id of the student
            $query->select('i.id')
                ->from('invoice_types as i')->join('payment_categories as p','i.payment_category_id','=','p.id' )
                ->whereRaw('p.short_name = "application_fee" and i.session_id = applicants.session_id');
        })         
        ->whereRaw('invoices.session_id = applicants.session_id and invoices.status = "paid"')->latest()->limit(1)->toSql();        

        $subquery2 = Invoice::select('status')
        ->whereRaw('owner_id= applicants.id and owner_type = "applicant" ')        
        ->whereIn('invoice_type_id', function ($query) {
            //SELECT ALL invoice type id for application fee with session id of the student
            $query->select('i.id')
                ->from('invoice_types as i')->join('payment_categories as p','i.payment_category_id','=','p.id' )
                ->whereRaw('p.short_name = "registration_fee" and i.session_id = applicants.session_id');
        })         
        ->whereRaw('invoices.session_id = applicants.session_id and invoices.status = "paid"')->latest()->limit(1)->toSql();        
        
        return $query2->selectRaw('IF(('.$subquery.') IS NULL , "Unpaid","Paid") as application_fee, IF(('.$subquery2.') IS NULL , "Unpaid","Paid") as registration_fee, applicants.*');            
        
    }   

    public function getUserTypeAttribute(){
        return 'applicant';
    }

    public function scopeQuery($query)
    {     
        return $query;
    }

    protected $appends = ['matric_number','qualify' ,'level', 'programme_name', 'programme_type','entry_mode', 'active_state', 'state','country','faculty','department','lga', 'qualification', 'full_name','admitted_programme_name','user_type'];
}
