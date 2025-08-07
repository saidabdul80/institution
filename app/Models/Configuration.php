<?php

namespace App\Models;

use App\Casts\RemoveWhiteSpace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'value', 'description', 'model', 'seeds'];
    protected $hidden = ['seeds'];
    protected $casts = [
        "value" => RemoveWhiteSpace::class
    ];
    public function getDataAttribute(){
        if($this->model != NULL){
            if($this->id == 29 ){
                return $this->model::where('country_id', tenant('country_id'))->get(); 
            }else{
                return $this->model::all();
            }
        }else if( $this->seeds != NULL){
            $array = explode(',', $this->seeds);
            foreach($array as $index => &$value ){
                $array[$index] = ['id'=> $value, 'name'=>$value];
            }
            return $array;
        }
    }
    
    public function getTitleAttribute(){
        return str_replace('_',' ',$this->name);
    }

    public function getProgrammeTypeAttribute(){
        return ProgrammeType::find($this->programme_type_id)?->short_name;
    }
    
    protected $appends = ['title','data','programme_type'];
}
