<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'booking_id','customer_id','provider_id','service_id',
        'rating','review_text','photo_urls','provider_response','is_flagged'
    ];
    protected $casts = ['photo_urls' => 'array', 'is_flagged' => 'boolean'];

    public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
    public function provider() { return $this->belongsTo(User::class, 'provider_id'); }
}
