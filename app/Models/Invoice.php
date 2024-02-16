<?php

namespace App\Models;

use App\Traits\Searchable;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, Searchable,  Filterable;
 
    protected $fillable = ['id',  'owner_id', 'charges', 'owner_type', 'invoice_type_id', 'session_id', 'invoice_number', 'amount', 'description', 'status', 'paid_at', 'confirmed_by', 'expected_charges', 'deleted_by', 'meta_data', 'deleted_at'];
    protected $appends = ['owner','invoice_name', 'full_name', 'matric_number', 'payment_category', 'payment_category_id', 'session_name', 'programme_id','total_amount','application_number'];
    //protected $with = ['owner'];
    protected $casts =[
        "created_at" =>"datetime:Y-m-d"
    ];

    public function modelFilter()
    {
        return $this->provideFilter(\App\ModelFilters\InvoiceFilter::class);
    }

    public function scopeFilterAccommodation($q)
    {
        $payment_category = PaymentCategory::where('short_name', 'accommodation_fee')->first();
        $invoice_type_ids = InvoiceType::where('payment_category_id', $payment_category->id)->pluck('id');
        return $q->wherein('invoice_type_id', $invoice_type_ids);
    }

    public function getInvoiceName()
    {
        $invoice = InvoiceType::find($this->invoice_type_id);
        return "{$invoice->name}";
    }

    public function getInvoiceNameAttribute()
    {
        $invoice = InvoiceType::find($this->invoice_type_id);
        return $invoice?->name;
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function payment()
    {
        return $this->payments()->where('status', 'successful');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, "owner_id", "id");
    }

    public function getFullNameAttribute()
    {        
        if ($this->invoiceType()?->owner_type === 'student') {
            $student = Student::where("id", $this->owner_id)->first();
            return "{$student?->first_name} {$student?->middle_name} {$student?->surname}";
        } else {
            $applicant = Applicant::where("id", $this->owner_id)->first();
            if (!empty($applicant)) {
                return "{$applicant->first_name} {$applicant->middle_name} {$applicant->surname}";
            }
            return '';
        }
    }

    public function getMatricNumberAttribute()
    {
        if ($this->invoiceType()?->owner_type === 'student') {
            $student = Student::where("id", $this->owner_id)->first();
            return $student?->matric_number;
        }
        return '';
    }

    public function getApplicationNumberAttribute()
    {
        if ($this->invoiceType()?->owner_type === 'applicant') {                   
            $applicant = Applicant::where("id", $this->owner_id)->first();
            return $applicant?->application_number;            
        }
        return '';
    }

    public function getProgrammeIdAttribute()
    {
        if ($this->owner_type === 'student') {
            return Student::where("id", $this->owner_id)->first()?->programme_id;
        } else if($this->owner_type === 'applicant') {
            return Applicant::where("id", $this->owner_id)->first()?->programme_id;          
        }
    }

    public function getPaymentCategoryAttribute()
    {
        $invoice_type = InvoiceType::where('id', $this->invoice_type_id)->first();
        $payment_category = PaymentCategory::where('id', $invoice_type?->payment_category_id)->first();
        return $payment_category->short_name ?? "";
    }

    public function getPaymentCategoryIdAttribute()
    {
        $invoice_type = InvoiceType::where('id', $this->invoice_type_id)->first();
        return $invoice_type?->payment_category_id;
    }

    public function paymentCategoryName()
    {
        $invoice_type = InvoiceType::where('id', $this->invoice_type_id)->first();
        return $invoice_type?->payment_short_name ?? "";
    }


    public function getSessionNameAttribute()
    {
        return Session::where('id', $this->session_id)->first()->name ?? "";
    }

    public function getSemesterNameAttribute()
    {
        return Semester::where('id', $this->session_id)->first()->name ?? "";
    }


    public function applicant()
    {
        if ($this->invoiceType()->owner_type === 'applicant') {
            return $this->hasOne(Applicant::class, 'id', 'owner_id');
        }
    }
    public function owner()
    {
        if ($this->owner_type == 'applicant') {
            return $this->belongsTo(Applicant::class);
        } else {
            return $this->belongsTo(Student::class);
        }
    }

    public function getOwnerAttribute()
    {
        if ($this->owner_type == 'applicant') {
            return Applicant::find($this->owner_id);
        } else {
            return Student::find($this->owner_id);
        }
    }


    public function invoice_type()
    {
        return $this->belongsTo(InvoiceType::class);
    }

    public function invoiceType()
    {
        return InvoiceType::find($this->invoice_type_id);
    }

    // public function getPaidAtAttribute()
    // {
    //     return $this->paid_at?->format('Y-m-d h:ia');
    // }

    public function getChargesAttribute($value)
    {
        if ($this->status != 'paid') {
            return $this->paymentCharge()?->amount ?? 0.00;
        }
        return $value;
    }

    public function getTotalAmountAttribute($value)
    {
        return $this->amount + $this->charges;                
    }


    public function paymentCharge()
    {
        $paymentCategoryId = TenantPaymentCategory::where('short_name', $this->paymentCategoryName())->first()?->id;
        return TenantPaymentCharge::where(['payment_category_id' => $paymentCategoryId, 'tenant_id' => tenant('id')])->first();
    }


    public function getConfirmedByAttribute($value)
    {
        if (!is_null($value))
        {
            return Staff::find($value)?->full_name;
        }else
        {
            return $value;
        }
    }

}
