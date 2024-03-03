<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class ALevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [];
    
    public function applicant(){
        return $this->belongsTo(Applicant::class);
    }
    protected $table = 'alevels';
    
    protected static function newFactory()
    {
        return \Modules\ApplicationPortalAPI\Database\factories\AlevelFactory::new();
    }

    public function certificates()
    {
        return $this->belongsTo(ApplicantCertificate::class, 'certificate_id');
    }
}
