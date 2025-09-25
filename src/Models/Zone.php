<?php

namespace Ingenius\Shipment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ingenius\Shipment\Enums\ZoneType;

class Zone extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'type',
        'active'
    ];

    protected $casts = [
        'type' => ZoneType::class,
        'active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Zone::class, 'parent_id');
    }

    public function municipalities(): HasMany
    {
        return $this->hasMany(Zone::class, 'parent_id')->where('type', ZoneType::MUNICIPALITY);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'parent_id');
    }

    public function scopeProvinces($query)
    {
        return $query->where('type', ZoneType::PROVINCE);
    }

    public function scopeMunicipalities($query)
    {
        return $query->where('type', ZoneType::MUNICIPALITY);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function isProvince(): bool
    {
        return $this->type === ZoneType::PROVINCE;
    }

    public function isMunicipality(): bool
    {
        return $this->type === ZoneType::MUNICIPALITY;
    }
}