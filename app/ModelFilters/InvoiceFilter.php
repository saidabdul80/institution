<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class InvoiceFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    protected $drop_id = false;

    public function programmes($search)
    {
        $filters = $this->input();        
        $ownerType = isset($filters['owner_type']) ? $filters['owner_type'] : 'applicant';
        $programme_ids = $search;
        return $this->query->where('owner_type', $ownerType)
            ->whereIn('owner_id', function ($query) use ($ownerType, $programme_ids) {
                $query->select('id')
                    ->from(function ($query) use ($ownerType, $programme_ids) {
                        $query->select('id', 'programme_id')
                            ->from($ownerType . 's')
                            ->whereIn('programme_id', $programme_ids);
                    });
            });
    }

    public function levels($search)
    {
        $filters = $this->input();        
        $ownerType = isset($filters['owner_type']) ? $filters['owner_type'] : 'applicant';
        $level_ids = $search;
        return $this->query->where('owner_type', $ownerType)
            ->whereIn('owner_id', function ($query) use ($ownerType, $level_ids) {
                $query->select('id')
                    ->from(function ($query) use ($ownerType, $level_ids) {
                        $query->select('id', 'level_id')
                            ->from($ownerType . 's')
                            ->whereIn('level_id', $level_ids);
                    });
        });
    }

    public function status($search){
        return $this->where(function($q) use ($search)
        {            
            return $q->whereIn('status',$search);               
        });
    }

    public function ownerType($search){
        return $this->where('owner_type',$search);
    }

    public function paymentCategoryId($search)
    {                
        return $this->join('invoice_types', 'invoice_types', "=", "invoices.invoice_type_id")
                        ->whereIn('invoice_types.payment_category_id', $search); 
    }   

    public function entryLevelId($search){
        return $this->whereHas('student',function($query) use($search){
            $query->where('entry_level_id',$search);
        });
    }

}
