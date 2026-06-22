<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'customer_id','provider_id','service_id','address_id','slot_id',
        'scheduled_at','notes','status','base_amount','discount_amount',
        'tax_amount','total_amount','payment_status','coupon_code',
        'cancelled_by','cancellation_reason','completed_at','job_photos','payout_status'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'base_amount'  => 'float',
        'discount_amount' => 'float',
        'tax_amount'   => 'float',
        'total_amount' => 'float',
        'job_photos'   => 'array',
    ];

    public function customer()     { return $this->belongsTo(User::class, 'customer_id'); }
    public function provider()     { return $this->belongsTo(User::class, 'provider_id'); }
    public function service()      { return $this->hasOne(Service::class, 'id', 'service_id'); }
    public function address()      { return $this->belongsTo(CustomerAddress::class, 'address_id'); }
    public function payment()      { return $this->hasOne(Payment::class); }
    public function review()       { return $this->hasOne(Review::class); }
    public function statusHistory(){ return $this->hasMany(BookingStatusHistory::class); }
    public function notes()        { return $this->hasMany(BookingNote::class); }
}
