<?php
namespace App\Traits;

use App\Repositories\ConfigurationRepository;

trait Searchable
{

    /**
     * scope to search a model for records based on an
     * array of column-value pairs
     */
    public function scopeSearch($query, $columns)
    {
        foreach ($columns as $column => $value) {
            if (!empty($value))
            {
                $query->where(function ($q) use ($column, $value) {
                    $q->where($column, 'like', $value)
                        ->orWhere($column, 'like', '%');
                });
            }else {
                continue;
            }
        }
        return $query;
    }

    /**
     * scope to match a record in a model based on an
     * array of column-value pairs
     */
    public function scopeMatch($query, $columns)
    {
        foreach ($columns as $column => $value) {  
            if (is_null($value)) {
                $columnName = ucwords(str_replace('_', ' ', explode('_id', $column)[0]));
    
                if ($column == 'programme_type_id') {
                    if (ConfigurationRepository::check('enable_programme_type', 'true')) {
                        throw new \Exception("Please fill in your $columnName detail", 404);
                    }
                }
    
                if ($column == 'entry_mode_id' || $column == 'mode_of_entry_id') {
                    if (ConfigurationRepository::check('enable_entry_mode', 'true')) {
                        throw new \Exception("Please fill in your $columnName detail", 404);
                    }
                }
    
                if ($column == 'faculty_id' || $column == 'department_id') {
                    if (ConfigurationRepository::check('enable_department', 'true')) {
                        throw new \Exception("Please fill in your $columnName detail", 404);
                    }
                }
            } else {                
                $query->where(function ($q) use ($column, $value) {
                    $q->where($column, 'like', $value)
                    ->orWhereNull($column)->orWhere($column,'');                    
                });
            }
        }
    
        // Ensure to return the modified query
        return $query;
    }
    
}
?>