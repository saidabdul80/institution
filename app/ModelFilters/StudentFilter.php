<?php 

namespace App\ModelFilters;

use App\Traits\FilterMethods;
use EloquentFilter\ModelFilter;
use Illuminate\Support\Facades\DB;

class StudentFilter extends ModelFilter
{    
    use FilterMethods;
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];
    
    public function matricNumber($search)
    {        
            return $this->where('matric_number', 'LIKE', "%".$search."%");                
    }    

    public function levels($levels)
    {
        return $this->whereHas('enrollments', function ($query) use ($levels) {
            $query->where('owner_type', 'student')
                ->whereIn('level_id_to', $levels)
                ->whereRaw('NOT EXISTS (
                    SELECT 1
                    FROM student_enrollments AS subsequent_enrollments
                    WHERE subsequent_enrollments.owner_id = student_enrollments.owner_id
                        AND subsequent_enrollments.owner_type = student_enrollments.owner_type
                        AND subsequent_enrollments.level_id_to > student_enrollments.level_id_to                            
                )');
        });
        
        /*  $level_id = 0;
        $nextLevelIdList = DB::table('levels')->orderBy('order')->get()->filter(function($l) use($order,&$level_id){
            if((int) $l->order == (int) $order){
                $level_id =(int) $l->order; 
            }
            return (int) $l->order > (int) $order;
        })->pluck('id');
        
        return $this->whereExists(function ($query) use ($level_id, $nextLevelIdList) {
            $query->selectRaw('1')
                ->from('student_enrollments')                  
                ->whereRaw('students.id = student_enrollments.owner_id')
                ->where('student_enrollments.owner_type', '=', 'student')
                ->whereRaw("level_id_to = $level_id")
                ->whereNotExists(function ($query) use ($nextLevelIdList) {
                    //ensure student is currently not in another level higher
                    $query->select(DB::raw(1))
                        ->from('student_enrollments as se2')                            
                        ->whereRaw('se2.owner_id = student_enrollments.owner_id')
                        ->where('se2.owner_type', '=', 'student')
                        ->whereIn('level_id_to',$nextLevelIdList);                            
                });
        });    */      
        
    }
}
