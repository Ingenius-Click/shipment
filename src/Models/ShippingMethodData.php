<?php

namespace Ingenius\Shipment\Models;

use Illuminate\Database\Eloquent\Model;
use Ingenius\Shipment\Services\ShippingMethodsManager;
use Ingenius\Shipment\ShippingMethods\AbstractShippingMethod;

class ShippingMethodData extends Model
{
    protected $table;

    protected $fillable = [
        'shipping_method_id',
        'name',
        'calculation_data',
        'active'
    ];

    protected $casts = [
        'calculation_data' => 'array',
        'active' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('shipment.shipping_methods_table', 'shipping_methods');
    }
}
