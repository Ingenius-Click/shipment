<?php

namespace Ingenius\Shipment\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'shippable_id',
        'shippable_type',
        'tracking_number',
        'shipping_method_id',
        'beneficiary_name',
        'beneficiary_email',
        'beneficiary_address',
        'beneficiary_city',
        'beneficiary_state',
        'beneficiary_zip',
        'beneficiary_country',
        'beneficiary_phone',
        'pickup_address',
        'base_currency_code',
        'currency_code',
        'exchange_rate',
        'base_amount',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
