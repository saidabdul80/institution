<?php
namespace Modules\Staff\Services;

use App\Exports\InvoicesExport;
use App\Models\Invoice;

use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceService extends Utilities{



    private function formatToDateTime($date){
        if($date != ''){
            return Carbon::parse($date)->format('Y-m-d');
        }
        return '';
    }

    public function exportInvoice($request){
        $session_id = $request->get('session_id');
        $payment_short_name = $request->get('payment_short_name')??"";
        $from =  $request->get('from');
        $to = $request->get('to');
        $filters = $request->get('filters')??[];

        $from = $this->formatToDateTime($from);
        $to = $this->formatToDateTime($to);
        //dd($from);
        $invoices = Invoice::filter($filters)->whereBetween('created_at', [$from , $to])->where('session_id',$session_id)->get();      
        //return $invoices;
        if($payment_short_name != ''){
            $filtered_invoices = $invoices?->where('payment_category',$payment_short_name)->makeHidden([
                "id",
                "owner_id",
                "invoice_type_id",
                "deleted_by",
                "meta_data",
                "deleted_at",
                "expected_charges",
                "payment_category",
                "payment_category_id",
                "session_id",
                "programme_id",
                "owner"
            ]);
        }else{
            $filtered_invoices = $invoices->makeHidden([
                "id",
                "owner_id",
                "invoice_type_id",
                "deleted_by",
                "meta_data",
                "deleted_at",
                "expected_charges",
                "payment_category",
                "payment_category_id",
                "session_id",
                "programme_id",
                "owner"
            ]);
        }        
        
        if($filtered_invoices->count() < 0){
            throw new \Exception('No records founds', 404);
        }

        $response = Excel::download(new InvoicesExport($filtered_invoices), 'invoices.xlsx');
        ob_end_clean();
        return  $response;

    }

}
