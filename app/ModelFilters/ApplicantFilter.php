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
        return $this->where(function($q) use ($search)
        {
            return $q->whereIn('qualified_status', $search);
        });
    }

    public function admissionStatus($search)
    {
        return $this->where(function($q) use ($search)
        {
            return $q->whereIn('admission_status',$search);
        });
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
            return $q->whereIn('applied_programme_id',$search);
        });
    }

    public function levels($search)
    {
        return $this->where(function($q) use ($search)
        {
            return $q->whereIn('applied_level_id',$search);
        });
    }
}
