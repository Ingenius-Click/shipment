<?php

namespace Ingenius\Shipment\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ProvinceAndMunicipalityMethodFeature implements FeatureInterface {
    public function getIdentifier(): string
    {
        return 'province-municipality-method';
    }

    public function getName(): string
    {
        return __('Province and Municipality method');
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