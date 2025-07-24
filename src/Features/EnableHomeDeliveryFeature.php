<?php

namespace Ingenius\Shipment\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class EnableHomeDeliveryFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'enable-home-delivery';
    }

    public function getName(): string
    {
        return 'Enable home delivery';
    }

    public function getPackage(): string
    {
        return 'shipment';
    }

    public function isBasic(): bool
    {
        return false;
    }
}
