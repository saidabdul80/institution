<?php
namespace Modules\Staff\Repositories;

use App\Models\Applicant;

class ApplicantRepository{

    private $applicant;
    public function __construct(Applicant $applicant)
    {
      $this->applicant = $applicant;
    }

    public function update($data,$id)
    {                        
        $this->applicant->where('id',$id)->update($data);                       
        return 'Updated successfuly';
    }

    public function getApplicantsWithoutPaginate($filters)
    {
        return $this->applicant->filter($filters)->get();     
    }

    public function getApplicantsWithoutAppends($filters=[])
    {    
        $this->applicant::$withoutAppends = true;
        return $this->applicant->filter($filters)->get();     
    }
}
