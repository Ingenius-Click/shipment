<?php

namespace Ingenius\Shipment\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class SelectLocalPickupMethodFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'select-local-pickup-method';
    }

    public function getName(): string
    {
        return __('Select local pickup method');
    }

    public function getGroup(): string
    {
        return __('Shipment');
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
