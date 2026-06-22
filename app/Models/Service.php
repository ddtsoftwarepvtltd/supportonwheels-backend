<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'category_id','name','description','base_price','price_type',
        'estimated_duration_min','icon','color','tag','is_active'
    ];
    protected $casts = ['is_active' => 'boolean', 'base_price' => 'float'];

    public function category() { return $this->belongsTo(ServiceCategory::class, 'category_id'); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
