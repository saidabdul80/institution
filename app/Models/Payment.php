<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $with = ['invoice'];
    protected $casts = ['created_at'=>'datetime:Y-m-d'];
    protected $fillable = ['invoice_id','paid_amount', "amount", "ourTrxRef", "payment_reference", "session_id", "owner_id", "owner_type", "payment_mode"];
    protected $appends = ['owner'];
 

    public function getProgrammeIdAttribute()
    {
        if ($this->owner_type === 'student') {
            $student = Student::where("id", $this->owner_id)->first();
            return $student->programme_id;
        } else {
            $applicant = Applicant::where("id", $this->owner_id)->first();
            if (!empty($applicant)) {
                return $applicant->programme_id;
            }
            return '';
        }
    }

    public function paymentCategoryName()
    {
      return Invoice::find($this->invoice_id)->paymentCategoryName();
    }


    public function paymentCharge()
    {
        $paymentCategoryId = TenantPaymentCategory::where('short_name', $this->paymentCategoryName())->first()->id;
        return TenantPaymentCharge::where(['payment_category_id' => $paymentCategoryId, 'tenant_id' => tenant('id')])->first();
    }

    public function applicant()
    {
        if ($this->owner_type === 'applicant') {
            return $this->hasOne(Applicant::class, 'id', 'owner_id');
        }
    }

    public function student()
    {
        if ($this->owner_type === 'student') {
            return $this->hasOne(Student::class, 'id', 'owner_id');
        }
    }

    public function owner()
    {
        if ($this->owner_type == 'student') {
            return $this->belongsTo(Student::class);
        } else {
            return $this->belongsTo(Applicant::class);
        }
    }

    public function getOwnerAttribute()
    {        
        if ($this->owner_type === 'student') {
            $student = Student::where("id", $this->owner_id)->first();
            return [
                "fullname"=>"{$student?->first_name} {$student?->middle_name} {$student?->surname}",
                "number" =>$student?->matric_number,
                "email"=>$student->email
            ];
        } else {
            $applicant = Applicant::where("id", $this->owner_id)->first();
            return [
                "fullname"=>"{$applicant?->first_name} {$applicant?->middle_name} {$applicant?->surname}",
                "number" =>$applicant?->matric_number,
                "email"=>$applicant?->email
            ];            
        }
    }

    public function invoice(){
        return $this->belongsTo(Invoice::class);
    }

}
