<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ServiceSlot extends Model
{
    protected $fillable = [
        'service_id','date','time_start','time_end',
        'max_bookings','booked_count','is_available'
    ];
    protected $casts = ['is_available' => 'boolean', 'date' => 'date'];
}
