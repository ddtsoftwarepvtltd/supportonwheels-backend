<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BookingNote extends Model
{
    protected $fillable = ['booking_id', 'admin_id', 'note'];
    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
