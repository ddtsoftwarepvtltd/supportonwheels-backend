<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantProfile extends Model
{
    protected $fillable = [
        'user_id', 'ndis_number', 'plan_management_type',
        'address', 'suburb', 'state', 'postcode',
        'latitude', 'longitude',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
