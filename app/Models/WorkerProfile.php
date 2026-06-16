<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerProfile extends Model
{
    protected $fillable = [
        'user_id', 'address', 'suburb', 'state', 'postcode',
        'latitude', 'longitude', 'ndis_screening_number',
        'ndis_expiry', 'first_aid_cert', 'first_aid_expiry',
        'is_online', 'rating', 'total_shifts',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
