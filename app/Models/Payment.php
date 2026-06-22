<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id','gateway_order_id','gateway_payment_id',
        'amount','currency','method','status','refund_amount','refunded_at'
    ];
    protected $casts = ['amount' => 'float', 'refund_amount' => 'float', 'refunded_at' => 'datetime'];

    public function booking() { return $this->belongsTo(Booking::class); }
}
