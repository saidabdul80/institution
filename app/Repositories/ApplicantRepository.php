<?php 
namespace App\Repositories;

use App\Models\Applicant;

class ApplicantRepository
{
    protected $applicant;

    public function __construct(Applicant $applicant)
    {
        $this->applicant = $applicant;
    }

    public function getById($id)
    {
        return $this->applicant->find($id);
    }
}