<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamType extends Model
{
    use HasFactory;
    protected $table = "exam_types";

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\ApplicationPortalAPI\Database\factories\ExamTypeFactory::new();
    }
}
