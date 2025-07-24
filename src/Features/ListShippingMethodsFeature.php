<?php

namespace Ingenius\Shipment\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ListShippingMethodsFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'list-shipping-methods';
    }

    public function getName(): string
    {
        return 'List shipping methods';
    }

    public function getPackage(): string
    {
        return 'shipment';
    }

    public function isBasic(): bool
    {
        return true;
    }
}
