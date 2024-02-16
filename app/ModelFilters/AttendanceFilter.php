<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class AttendanceFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    protected $drop_id = false;
    public $relations = [];

    public function byDate($search)
    {
        return $this->whereDate('attendance_date', $search);                
    }

    public function sessionId($search){
        return $this->where('session_id', $search);
    }

    public function armId($search){
        return $this->where('arm_id', $search);
    }

    public function classId($search){
        return $this->where('class_id', $search);
    }

    public function status($search){
        return $this->where('status', $search);
    }

    public function studentId($search){
        return $this->where('student_id', $search);
    }

    public function byDateRange($search)
    {
        [$startDate, $endDate] = $search;
        return $this->whereBetween('attendance_date', [$startDate, $endDate]);                        
    }

}

?>