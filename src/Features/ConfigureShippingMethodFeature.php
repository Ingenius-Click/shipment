<?php

namespace Ingenius\Shipment\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ConfigureShippingMethodFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'configure-shipping-method';
    }

    public function getName(): string
    {
        return __('Configure shipping method');
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
