<?php

namespace Ingenius\Shipment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the beneficiary.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(tenant_user_class());
    }
}
