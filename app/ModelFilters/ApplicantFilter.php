<?php

namespace App\ModelFilters;

use App\Traits\FilterMethods;
use EloquentFilter\ModelFilter;

class ApplicantFilter extends ModelFilter
{
    use FilterMethods;
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function qualifiedStatus($search)
    {
        
        if(is_array($search)){
            $this->whereIn('qualified_status', $search);
        }else{
            $this->where('qualified_status', $search);
        }
       
    }

    public function admissionStatus($search)
    {
        if(is_array($search)){
            $this->whereIn('admission_status', $search);
        }else{
            $this->where('admission_status', $search);
        }
       
    }

    public function paymentStatus($search)
    {    
       $cond = "";
        $search = array_map('strtolower', $search);
    
        if (in_array('unpaid', $search)) {
            $cond = "application_fee = 'Unpaid'";
        }
    
        if (in_array('paid', $search)) {
            if ($cond != '') {
                $cond .= " OR application_fee = 'Paid'";
            } else {
                $cond = "application_fee = 'Paid'";
            }
        }
    
        if ($cond != '') {
            return $this->query()->havingRaw($cond);
        } else {
            return $this->query();
        }      
    }

    public function SchoolFeePaymentStatus($search)
    {
        $cond = "";
        $search = array_map('strtolower', $search);
    
        if (in_array('unpaid', $search)) {
            $cond = "registration_fee = 'Unpaid'";
        }
    
        if (in_array('paid', $search)) {
            if ($cond != '') {
                $cond .= " OR registration_fee = 'Paid'";
            } else {
                $cond = "registration_fee = 'Paid'";
            }
        }
    
        if ($cond != '') {
            return $this->query()->havingRaw($cond);
        } else {
            return $this->query();
        }
    }

    public function programmes($search)
    {
        return $this->where(function($q) use ($search)
        {
            return $q->whereIn('applied_programme_curriculum_id',$search);
        });
    }

    public function levels($search)
    {
        return $this->where(function($q) use ($search)
        {
            return $q->whereIn('applied_level_id',$search);
        });
    }

    public function applicationFeePaid($search)
    {
        return $this->where('application_fee_paid', $search);
    }

    public function acceptanceFeePaid($search)
    {
        return $this->where('acceptance_fee_paid', $search);
    }

    public function verificationStatus($search)
    {
        return $this->where('verification_status', $search);
    }

    public function is_imported($search)
    {
        return $this->where('is_imported', $search);
    }
}
