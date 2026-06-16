<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id', 'organisation_name', 'abn',
        'is_ndis_registered', 'ndis_registration_number',
        'address', 'suburb', 'state', 'postcode',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
