<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id','kyc_status','is_online','rating','total_jobs','acceptance_rate',
        'bank_account_no','bank_ifsc','last_location_lat','last_location_lng','last_location_at',
        'bio','service_ids','weekly_schedule','leave_dates','is_featured','rejection_reason',
        'government_id_url','police_verification_url','skills_certificate_url',
        // From existing project
        'organisation_name','abn','is_ndis_registered','ndis_registration_number',
        'address','suburb','state','postcode',
    ];
    protected $casts = [
        'is_online'       => 'boolean',
        'is_featured'     => 'boolean',
        'is_ndis_registered' => 'boolean',
        'rating'          => 'float',
        'acceptance_rate' => 'float',
        'service_ids'     => 'array',
        'weekly_schedule' => 'array',
        'leave_dates'     => 'array',
        'last_location_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
