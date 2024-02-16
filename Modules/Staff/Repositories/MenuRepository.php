<?php
namespace Modules\Staff\Repositories;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\DB;

class MenuRepository{
         
    public function __construct()
    {        
        
    }

    public function exists($id, $title, $model){
        if(is_null($id)){
            return $model::where('title', $title)->exists();
        }
        return $model::where('title', $title)->where('id','!=',$id)->exists();
    }

    public function update($id, $data, $model){
        
        return $model::where('id',$id)->update($data);                       

    }

    public function create($data,$model){   
        if($this->exists(null,$data['title'], $model)){
            throw new \Exception('title already exist', 400);
        }             

        $model::insert($data);                
        return 'Created Successfully';
    }

    public function delete($id,$model){

            $response = $model::find($id);

            if($response){                
                    return $response->delete();                                
            }else{
                throw new \Exception('Data Not Found', 404);  ;
            }             
    }

    public function unDelete($id,$model){
                
        $response = $model::onlyTrashed()->find($id);
        if($response){
            $response->restore();                
        }else{
            throw new \Exception('Data Not Found', 404);  ;
        }        
        
    }

    public function fetch($model){
        return $model::get();
    } 

}
