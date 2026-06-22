<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'provider_id','amount','gross_amount','fee',
        'type','status','bank_account','bank_ifsc'
    ];
    protected $casts = ['amount' => 'float', 'gross_amount' => 'float', 'fee' => 'float'];

    public function provider() { return $this->belongsTo(User::class, 'provider_id'); }
}
