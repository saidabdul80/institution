<?php

namespace App\Models;

use App\Traits\Searchable as TraitsSearchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ProgrammeTypes;

class InvoiceType extends Model
{
    use HasFactory, TraitsSearchable;

    protected $table = "invoice_types";
    public $timestamps = true;

    public function payment_category()
    {
        return $this->belongsTo(PaymentCategory::class);
    }

    public function programmeType()
    {
        return $this->belongsTo(ProgrammeType::class);
    }

    public function getProgrammeNameAttribute()
    {
        $programme = Programme::find($this->applied_programme_id);
        if (!is_null($programme)) {
            return "{$programme->name}";
        } else {
            return '';
        }
    }


    public function getLevelAttribute()
    {
        $level = Level::find($this->applied_level_id);
        if (!is_null($level)) {
            return "{$level->title}";
        } else {
            return '';
        }
    }

    public function getEntryModeAttribute()
    {
        $entry_mode = EntryMode::find($this->mode_of_entry_id);
        if (!is_null($entry_mode)) {
            return "{$entry_mode->entry_mode}";
        } else {
            return '';
        }
    }

    public function getProgrammeAttribute()
    {
        $programme = Programme::find($this->programme_id);
        if (!is_null($programme)) {
            return "{$programme->title}";
        } else {
            return '';
        }
    }

    public function getProgrammeTypeAttribute()
    {
        $programmeType = ProgrammeType::find($this->programme_type_id);
        if (!is_null($programmeType)) {
            return "{$programmeType->name}";
        } else {
            return '';
        }
    }

    public function getStateAttribute()
    {
        $state = State::find($this->state_id);
        if (!is_null($state)) {
            return "{$state->name}";
        } else {
            return '';
        }
    }

    public function getCountryAttribute()
    {
        $country = Country::find($this->country_id);
        if (!is_null($country)) {
            return "{$country->name}";
        } else {
            return '';
        }
    }

    public function getFacultyAttribute()
    {
        $faculty = Faculty::find($this->faculty_id);
        if (!is_null($faculty)) {
            return "{$faculty->name}";
        } else {
            return '';
        }
    }

    public function getDepartmentAttribute()
    {
        $department = Department::find($this->department_id);
        if (!is_null($department)) {
            return "{$department->name}";
        } else {
            return '';
        }
    }


    public function getLgaAttribute()
    {
        $lga = LGA::find($this->lga_id);
        if (!is_null($lga)) {
            return "{$lga->name}";
        } else {
            return '';
        }
    }

    public function getPaymentNameAttribute()
    {
        $paymentCategory = PaymentCategory::find($this->payment_category_id);
        if (!is_null($paymentCategory)) {
            return "{$paymentCategory->name}";
        } else {
            return '';
        }
    }

    public function getPaymentShortNameAttribute()
    {
        $paymentCategory = PaymentCategory::find($this->payment_category_id);
        if (!is_null($paymentCategory)) {
            return "{$paymentCategory->short_name}";
        } else {
            return '';
        }
    }

    public function charges()
    {
        return TenantPaymentCharge::where(['payment_category_id' => $this->payment_category_id, 'tenant_id' => tenant('id')])->first()?->charge();
    }

    public function getTotalAmountAttribute()
    {
        return number_format($this->amount + $this->charges(), 2,'.','');
    }

    public function scopeFilterAccomodationFee($query)
    {
        $accommodationFeeId = PaymentCategory::where('short_name', 'accommodation_fee')->first()?->id;
        return $query->where('payment_category_id','!=', $accommodationFeeId);
    }

    protected $appends = ['total_amount','payment_name', 'payment_short_name', 'programme_type', 'level', 'programme', 'entry_mode', 'state', 'country', 'faculty', 'department', 'lga'];
}
