<?php
// ===== app/Models/CustomerAddress.php =====

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'user_id', 'label', 'full_address',
        'lat', 'lng', 'city', 'pincode', 'is_default',
    ];

    protected $casts = [
        'lat' => 'float', 'lng' => 'float', 'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
