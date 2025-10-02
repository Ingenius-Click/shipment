<?php

namespace Ingenius\Shipment\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class SelectHomeDeliveryMethodFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'select-home-delivery-method';
    }

    public function getName(): string
    {
        return __('Select home delivery method');
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
