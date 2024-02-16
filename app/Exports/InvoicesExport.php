<?php

namespace App\Exports;

use App\Models\Invoice;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicesExport implements FromCollection, WithHeadings
{
   
    private $filtered_invoices;
    public function __construct($filtered_invoices) {
        $this->filtered_invoices = $filtered_invoices;
    }
    
    /**
    * @return \Illuminate\Support\Collection
    */
    
    public function collection()
    {     
        return $this->filtered_invoices;
    }

      public function headings(): array
    {            
        
        $invoice = $this->filtered_invoices->take(1)->values()->toArray();  
        if(sizeof($invoice)>0){
            $invoice = $invoice[0];
            return array_keys($invoice);
        }
        
    }
}
