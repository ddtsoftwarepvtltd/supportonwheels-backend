<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['title','icon','tag','price','price_label','color','is_active'];

    protected $casts = ['is_active' => 'boolean', 'price' => 'float'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
