<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicantCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'name',
        'url',
        'filename',
        'certificate_type_id'
    ];
    protected $table = "applicants_certificates";
    
    protected static function newFactory()
    {
     //   return \Modules\ApplicationPortalAPI\Database\factories\ApplicantCertificateFactory::new();
    }

    public function alevel()
    {
        return $this->hasOne(Alevel::class);
    }
}
