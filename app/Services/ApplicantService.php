<?php 

namespace App\Services;

use App\Repositories\ApplicantRepository;

class ApplicantService
{
    protected $applicantRepository;

    public function __construct(ApplicantRepository $applicantRepository)
    {
        $this->applicantRepository = $applicantRepository;   
    }

    public function getApplicant($id)
    {
        return $this->applicantRepository->getById($id);
    }
}