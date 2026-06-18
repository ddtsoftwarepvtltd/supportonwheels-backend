<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'label',
        'parent_id',
    ];

    protected $casts = [
        'parent_id' => 'integer',
    ];
    protected $appends = ['label','display_label'];

    /**
     * Parent Permission
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child Permissions
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getLabelAttribute(): string
    {
        return !empty($this->attributes['label'])
            ? $this->attributes['label']
            : ucwords(str_replace(['.', '_', '-'], ' ', $this->name));
    }

    protected function displayLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->label
                ?: ucwords(str_replace('.', ' ', $this->name))
        );
    }
}
