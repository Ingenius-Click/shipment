<?php

namespace Ingenius\Shipment\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class EnableLocalPickupFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'enable-local-pickup';
    }

    public function getName(): string
    {
        return 'Enable local pickup';
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
