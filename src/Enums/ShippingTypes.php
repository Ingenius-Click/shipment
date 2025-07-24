<?php

namespace Ingenius\Shipment\Enums;

enum ShippingTypes: string
{
    case LOCAL_PICKUP = 'local_pickup';
    case HOME_DELIVERY = 'home_delivery';
}
