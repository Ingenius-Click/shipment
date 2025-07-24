<?php

namespace Ingenius\Shipment\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class LocalPickupMethodFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'local-pickup-method';
    }

    public function getName(): string
    {
        return 'Local pickup method';
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
