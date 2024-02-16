<?php

namespace Modules\Staff\Repositories;

use App\Models\Department;
use Illuminate\Support\Facades\Http;
use Database\Seeders\Courses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function PHPUnit\Framework\isNull;

class DepartmentRepository
{

    private $department;
    public function __construct(Department $department)
    {
        $this->department = $department;
    }

    public function exists($id, $name, $abbr)
    {

        return $this->department::where(function ($query) use ($name, $abbr) {
            $query->where(['name' => $name,])->orWhere(['abbr' => $abbr,]);
        })->where('id', '!=', $id)->first();
    }

    public function update($id, $data)
    {

        $department = $this->department::find($id);
        foreach ($data as $key => $value) {
            if ($key != "id") {
                $department->$key = $value;
            }
        }
        $department->save();
        return $department;
    }

    public function create($data)
    {

        $department = $this->department::create($data);
        return $department;
    }


    public function delete($id)
    {

        $IsInUsed = $this->checkInstanceExist('departments', 'department_id', $id);
        if (empty($IsInUsed)) {
            $department = $this->department::find($id);
            if (!empty($department)) {
                try {
                    $department->delete();
                    return 'Deleted Successfuly';
                } catch (\Exception $e) {
                    throw new \Exception("Department could not be Deactivated");
                }
            } else {
                throw new \Exception('Department Not Found', 404);;
            }
        }
        throw new \Exception("Department could not be Deactivated");
    }

    private function checkInstanceExist($tablename, $foreignId, $id)
    {
        $sql = "SELECT DISTINCT f.id
                    FROM $tablename f LEFT JOIN applicants a ON a.$foreignId = f.id LEFT JOIN students s ON s.$foreignId = f.id
                    LEFT JOIN staffs d ON d.$foreignId = f.id WHERE
                    (CASE WHEN  a.$foreignId IS NULL
                        THEN
                        (CASE WHEN s.$foreignId IS NULL
                            THEN
                                (CASE WHEN d.$foreignId IS NULL
                                    THEN 0
                                    ELSE 1
                                    END
                                )
                            ELSE 1
                            END
                        )
                        ELSE 1
                    END)";

       $response = DB::select(DB::raw($sql));  //all model instances id used in another model
       $response = array_column($response,'id');
       if(in_array($id,$response)){
         return true; // this id is in used
       }
       return false;
    }

    public function unDelete($id)
    {

        $department = $this->department::onlyTrashed()->find($id);
        if ($department) {
            $department->restore();
        } else {
            throw new \Exception('Department Not Found', 404);;
        }
    }

    public function getData($search=null, $paginateBy=null){   
        $paginate = $paginateBy ?? 100;
        return $this->department::search($search)->latest()->paginate($paginate);        
    }

    public function getDataWithoutPaginate($search=null)
    {    
        return $this->department::search($search)->latest()->get();        
    }
    
    public function names()
    {
        return $this->department::pluck("name")->toArray();
    }

    public function abbrs()
    {
        return $this->department::pluck("abbr")->toArray();
    }

    public function uploadBulkData($data)
    {
        return $this->department::insert($data);
    }
}
