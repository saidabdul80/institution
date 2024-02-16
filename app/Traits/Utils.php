<?php
namespace App\Traits;
use Illuminate\Support\Facades\DB;
trait Utils{
    
    public function getLastNumberFromString($tablename, $column_name){
        return  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(SUBSTRING_INDEX(tablename.$column_name,'/',-1), int)) as lastNumber")->get(0);
     }
 
     public function searchUserBy($column_name,$value)
     {
         return Self::where($column_name, $value)->first();
     }
     
     public function searchData($serachParam,$paginateBy,$length=1, array $relationship = []){
        //return dd($serachParam);
         if($length == -1){
             if(sizeof($relationship) > 0){                
                 return self::Match($serachParam)->with($relationship)->get();                
             }
 
             return self::Match($serachParam)->get();
 
         }else{
             
             if(sizeof($relationship) > 0){
                 return self::Match($serachParam)->with($relationship)->paginate($paginateBy);
             }
             
             return self::Match($serachParam)->paginate($paginateBy);
         }
         
     }
    
     public function fetchData($paginateBy,$length=1, array $relationship = []){
 
         if(sizeof($relationship) > 0){
             return self::with($relationship)->paginate($paginateBy);
         }else{
             return self::paginate($paginateBy);
         }
 
     }

     
 
 
    
}