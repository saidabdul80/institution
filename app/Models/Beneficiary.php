<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Casts\ArrayCast;
class Beneficiary extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'beneficiary_type',
        'beneficiary_id',
        'account_number',
        'account_name',
        'bank_code',
        'currency',
        'recipient_code',
        'nuban',
        'name',
        'subaccount_code',
        'status',
        'options',
        'gateways'
    ];

    protected $appends = ['code'];

    public static function defaultGatewayOptions(){
        return [
            'remita' => false,
            'paystack' => false,
            config('default.etranzact.name') => false
        ];
    }

    protected function options(): Attribute
    {
        // Default options
        $defaultOptions = $this->defaultGatewayOptions();
        return Attribute::make(
            get: function ($value, $attributes) use ($defaultOptions) {
                // Ensure $value is an array
                $options = json_decode($value, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $options = [];  
                }
                // Merge default options with existing options
                // Existing options will override defaults if they exist
                return array_merge($defaultOptions, $options);
            },
            set: function ($value) {
                // Ensure the value is stored as an array
                return is_array($value) ? json_encode($value) : json_encode([]);
            }
        );
    }

     public function defaultGatewayMetaData(){
        $result = [];
        foreach($this->defaultGatewayOptions() as $gateway => $options){
            $result[$gateway] = [
                "code" =>"",
            ];
        }
        return $result;
    }

    protected function gateways(): Attribute
    {
        $defaultOptions = $this->defaultGatewayMetaData();
        
        return Attribute::make(
            get: function ($value, $attributes) use ($defaultOptions) {
                // Ensure $value is an array
                $options = json_decode($value, true) ?? [];
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $options = [];
                }

                // Deep merge with defaults
                $merged = $this->deepMergeGateways($defaultOptions, $options);
                
                return $merged;
            },
            set: function ($value) {
                return is_array($value) ? json_encode($value) : json_encode([]);
            }
        );
    }

    protected function deepMergeGateways(array $defaults, array $custom): array
    {
        $result = $defaults;
        
        foreach ($defaults as $gateway => $config) {
            
            // Merge gateway configurations
            $result[$gateway] = array_merge(
                $config, 
                is_array($custom) && isset($custom[$gateway]) ? $custom[$gateway] : []
            );
            
            // Ensure all required keys exist
            if ($gateway == 'paystack' &&  $result[$gateway]['code'] == '') {
                $result[$gateway]['code'] = $this->subaccount_code ?? '';
            }

            if ($gateway == 'remita' &&  $result[$gateway]['code'] == '') {
                   $result[$gateway]['code'] = $this->account_number ?? '';
            }

            if ($gateway == 'etranzact' &&  $result[$gateway]['code'] == '') {
                $result[$gateway]['code'] = $this->bank_code.$this->account_number;
            }
        }
        
        return $result;
    }

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //     ->logOnly(['name', 'beneficiary_type', 'beneficiary_id', 'account_number', 'account_name','bank_code', 'status']);
        
    // }

    /**
     * Get the parent beneficiary model (State or UBT).
     */
    public function beneficiaryable()
    {
        return $this->morphTo(__FUNCTION__, 'beneficiary_type', 'beneficiary_id');
    }
    

    /**
     * Set the beneficiary name.
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
    }

    /**
     * Get the beneficiary name.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        if (is_null($this->attributes['name']??null)) {
            return $this->beneficiaryable ? $this->beneficiaryable->name : null;
        }

        return $this->attributes['name'];
    }

    public function getStatusAttribute()
    {
        if (isset($this->attributes['status']) && $this->attributes['status'] == 1) {
            return true;
        }

        return false;
    }


  
   
  
}
