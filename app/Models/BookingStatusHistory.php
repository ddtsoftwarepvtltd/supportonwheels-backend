<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BookingStatusHistory extends Model
{
    protected $fillable = ['booking_id', 'status', 'changed_by', 'note'];
}
