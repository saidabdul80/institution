<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
class ApplicationType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [];
    protected $table = 'application_types';
    protected static function newFactory()
    {
        return \Modules\ApplicationPortalAPI\Database\factories\ApplicationTypeFactory::new();
    }

    public function applicants(){
        return $this->hasMany(Applicant::class);
    }

}
