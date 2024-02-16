<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
class Level extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\ApplicationPortalAPI\Database\factories\LevelFactory::new();
    }

    public function applicants(){
        return $this->hasMany(Applicant::class);
    }

}
