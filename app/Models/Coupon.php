<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code','discount_type','discount_value','max_discount_cap',
        'min_order_amount','usage_limit','per_user_limit','used_count',
        'expires_at','is_active','is_new_user_only'
    ];
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
        'is_new_user_only' => 'boolean',
        'discount_value' => 'float',
    ];

    public function usages() { return $this->hasMany(CouponUsage::class); }
}
