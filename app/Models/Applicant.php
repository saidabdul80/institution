<?php

namespace App\Models;


use App\Traits\Utils;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
//use Illuminate\Support\Facades\DB;
use EloquentFilter\Filterable;
use Illuminate\Support\Facades\Log;
// Add import for ProgrammeCurriculum - will be added when the model is created

class Applicant extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes, Utils, Filterable;


    protected $fillable = [
        "first_name", "middle_name", "surname", "phone_number", "gender", "email", "application_number", "batch_id", "session_id", "lga_id", "country_id", "state_id", "applied_level_id", "level_id", "applied_programme_curriculum_id","programme_curriculum_id", "programme_id", "programme_type_id", "mode_of_entry_id", "application_status_id", "department_id", "faculty_id", "years_of_experience", "working_class", "category", "present_address", "permanent_address", "guardian_full_name", "guardian_phone_number", "guardian_address", "guardian_email", "guardian_relationship", "sponsor_full_name", "sponsor_type", "sponsor_address", "next_of_kin_full_name", "next_of_kin_address", "next_of_kin_phone_number", "next_of_kin_relationship", "wallet_number", "prev_institution", "prev_year_of_graduation", "health_status", "health_status_description", "blood_group", "disability", "religion", "marital_status", "admission_status", "published_at", "final_submitted_at", "is_final_submitted", "final_submission_notes", "published_by", "publication_notes", "admission_serial_number", "qualified_status", "final_submission", "application_progress", "logged_in_time", "logged_in_count", "picture", "signatuare", "jamb_number", "jamb_subject_scores", "is_imported", "import_batch_id", "imported_at", "application_fee_paid", "documents_completed", "documents_completed_at", "application_fee_paid_at", "scratch_card", "entrance_exam_score", "entrance_exam_status", "deleted_at", "deleted_by", "password", "created_at", "updated_at", "jamb_score", "date_of_birth",
        "acceptance_fee_paid",
        "acceptance_fee_paid_at",
    ];

    protected $hidden =['password'];

    protected $casts = [
        'jamb_subject_scores' => 'array',
        'is_imported' => 'boolean',
        'application_fee_paid' => 'boolean',
        'imported_at' => 'datetime',
        'application_fee_paid_at' => 'datetime',
        'acceptance_fee_paid' => 'boolean',
        'acceptance_fee_paid_at' => 'datetime',
        'published_at' => 'datetime',
        'final_submitted_at' => 'datetime',
        'is_final_submitted' => 'boolean',
    ];
    /*  protected static function newFactory()
    {

        return \Database\factories\ApplicantFactory::new();
    } */
    protected $subjects;

    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->subjects = Subject::all();
    }

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
        return $this->belongsTo(Programme::class);
    }

    public function programmeCurriculum()
    {
        return $this->belongsTo(ProgrammeCurriculum::class, 'programme_curriculum_id');
    }

    public function level(){
        return $this->belongsTo(Level::class);
    }

    public function entryMode(){
        return $this->belongsTo(EntryMode::class,'mode_of_entry_id');
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

    public function session(){
        return $this->belongsTo(Session::class);
    }
    public function getSubmittedDateAgoAttribute(){
        return Carbon::parse($this->created_at)->diffForHumans();
    }
    public function getQualificationAttribute()
    {
        return Qualification::find($this->prev_qualification_id)?->name;
    }

    public function getProgrammeNameAttribute() {
        return Programme::find($this->applied_programme_curriculum_id)?->name;
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
    $olevelResults = OlevelResult::where('applicant_id', $this->id)->pluck('subjects_grades');
    $programme = Programme::where('id', $this->applied_programme_curriculum_id)->first();
    $required_subjects = $this->subjects->filter(function ($subject) use ($programme) {
        return in_array($subject->id, $programme->required_subjects);
    })->pluck('name')->toArray();
    $subjects = !empty($required_subjects) ?
                (is_array($required_subjects) ? $required_subjects : explode(',', $required_subjects)) : [];
    $grades = !empty($programme?->accepted_grades) ?
              collect(explode(',', $programme?->accepted_grades))
                  ->map(function($item) {
                      return strtoupper(trim($item)); // Normalize to uppercase and trim whitespace
                  })->toArray() : [];

    $checkSubject = [];
    $checkSubject2 = [];

    if (empty($subjects)) {
        return ["is_qualify" => true, "info" => 'no required subjects set'];
    }

    // Check if we should compare only the letter part (e.g., "A" from "A1")
    $compareLetterOnly = collect($grades)->contains(function ($grade) {
        return ctype_alpha($grade);
    });

    foreach ($olevelResults as $key => $olevelResult) {
        // Parse the result data
        $result = is_string($olevelResult) ? json_decode($olevelResult, true) : (array)$olevelResult;

        if (!is_array($result)) {
            continue;
        }

        // Process the grades, converting to uppercase
        $oResult = collect($result)->mapWithKeys(function ($grade, $subject) {
            return [ucfirst(strtolower($subject)) => strtoupper($grade)];
        });

        foreach ($subjects as $subject) {
            $subjectKey = ucfirst(strtolower($subject));

            if (!$oResult->has($subjectKey)) {
                $checkSubject[$subject] = $key == 0 ? 0 : ($checkSubject2[$subject] = 0);
                continue;
            }

            $subjectGrade = $oResult[$subjectKey];
            $gradeToCompare = $compareLetterOnly ? substr($subjectGrade, 0, 1) : $subjectGrade;

            if (in_array($gradeToCompare, $grades)) {
                $checkSubject[$subject] = $key == 0 ? 1 : ($checkSubject2[$subject] = 1);
            } else {
                $checkSubject[$subject] = $key == 0 ? 0 : ($checkSubject2[$subject] = 0);
            }
        }
    }

    $first = array_sum(array_values($checkSubject));
    $second = array_sum(array_values($checkSubject2));

    if ($first == count($subjects)) {
        return ["is_qualify" => true, "info" => 'One Result'];
    } elseif ($first + $second >= count($subjects)) {
        return ["is_qualify" => true, "info" => 'Two Result'];
    }

    return ["is_qualify" => false, "info" => 'Admission requirements not met'];
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

    public function getApplicationFeeAttribute(){

        return $this->application_fee_paid==1?'paid':'unpaid';
    }
    public function getUserTypeAttribute(){
        return 'applicant';
    }

    public function scopeQuery($query)
    {
        return $query;
    }

    public function batch(){
        return $this->belongsTo(AdmissionBatch::class,'batch_id');
    }

    // Publication related scopes and methods
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeUnpublished($query)
    {
        return $query->whereNull('published_at');
    }

    public function scopeAdmittedUnpublished($query)
    {
        return $query->where('admission_status', 'admitted')->whereNull('published_at');
    }

    public function isPublished()
    {
        return !is_null($this->published_at);
    }

    public function publish($publishedBy = null, $notes = null)
    {
        $this->update([
            'published_at' => now(),
            'published_by' => $publishedBy,
            'publication_notes' => $notes,
        ]);
    }

    public function unpublish()
    {
        $this->update([
            'published_at' => null,
            'published_by' => null,
            'publication_notes' => null,
        ]);
    }

    // Final submission methods
    public function isFinalSubmitted()
    {
        return $this->is_final_submitted;
    }

    public function finalSubmit($notes = null)
    {
        Log::info('Final submitted by ' ,[$this]);
        $this->update([
            'final_submitted_at' => now(),
            'is_final_submitted' => true,
            'final_submission_notes' => $notes,
        ]);
    }

    public function canEdit()
    {
        return !$this->is_final_submitted;
    }

    public function scopeFinalSubmitted($query)
    {
        return $query->where('is_final_submitted', true);
    }

    public function scopeNotFinalSubmitted($query)
    {
        return $query->where('is_final_submitted', false);
    }

    public function documents(){
        return $this->hasMany(Document::class,'owner_id')->where('owner_type','applicant');
    }

    protected $appends = ['matric_number','qualify' ,'level', 'programme_name', 'programme_type','entry_mode', 'active_state', 'state','country','faculty','department','lga', 'qualification', 'full_name','admitted_programme_name','user_type','submitted_date_ago',];
}
