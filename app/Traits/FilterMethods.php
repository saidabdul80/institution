<?php
namespace App\Traits;

trait FilterMethods{
    public function departments($search)
    {        
        return $this->where(function($q) use ($search)
        {            
            return $q->whereIn('department_id',$search);               
        });
    }
    public function programmeTypes($search)
    {        
        return $this->where(function($q) use ($search)
        {            
            return $q->whereIn('progamme_type_id',$search);               
        });
    }

    public function faculties($search)
    {        
        return $this->where(function($q) use ($search)
        {            
            return $q->whereIn('faculty_id',$search);               
        });
    }

    public function programmes($search)
    {        
        return $this->where(function($q) use ($search)
        {            
            return $q->whereIn('programme_id',$search);               
        });
    }

    public function gender($search)
    {        
        return $this->where(function($q) use ($search)
        {            
            return $q->whereIn('gender',$search);               
        });
    }

    public function maritalStatus($search)
    {        
        return $this->where(function($q) use ($search)
        {            
            return $q->whereIn('marital_status',$search);               
        });
    }

    public function status($search)
    {        
        if(is_array($search))
        {
            return $this->where(function($q) use ($search)
            {            
                return $q->whereIn('status',$search);               
            });
        }
        return $this->where('status',$search);               
    }

}