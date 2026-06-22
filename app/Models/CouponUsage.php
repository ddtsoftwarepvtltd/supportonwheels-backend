<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $fillable = ['coupon_id', 'user_id', 'booking_id', 'discount_amount'];
}
