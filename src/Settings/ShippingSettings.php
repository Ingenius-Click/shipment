<?php

namespace Ingenius\Shipment\Settings;

use Ingenius\Core\Settings\Settings;

class ShippingSettings extends Settings
{
    public bool $local_pickup_enabled = true;

    public string $local_pickup_method = 'local_pickup';

    public bool $home_delivery_enabled = false;

    public string $home_delivery_method = '';

    public static function group(): string
    {
        return 'shipping';
    }
}
