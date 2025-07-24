<?php

namespace Ingenius\Shipment\Services;

use Exception;
use Ingenius\Core\Facades\Settings;
use Ingenius\Shipment\Enums\ShippingTypes;
use Ingenius\Shipment\Settings\ShippingSettings;
use Ingenius\Shipment\ShippingMethods\AbstractShippingMethod;

class ShippingStrategyManager
{
    protected $shippingMethodManager;
    protected $shippingSettings;

    public function __construct(ShippingMethodsManager $shippingMethodManager)
    {
        $this->shippingMethodManager = $shippingMethodManager;
        $this->shippingSettings = app(ShippingSettings::class);
    }

    public function setLocalPickupMethod(string $shipping_method_id): void
    {
        $shippingMethod = $this->shippingMethodManager->getShippingMethod($shipping_method_id);

        if ($shippingMethod->getType() !== ShippingTypes::LOCAL_PICKUP) {
            throw new Exception("Shipping method is not a local pickup method: {$shippingMethod->getType()->value}");
        }

        $this->shippingSettings->local_pickup_method = $shipping_method_id;
        $this->shippingSettings->save();
    }

    public function setHomeDeliveryMethod(string $shipping_method_id): void
    {
        $shippingMethod = $this->shippingMethodManager->getShippingMethod($shipping_method_id);

        if ($shippingMethod->getType() !== ShippingTypes::HOME_DELIVERY) {
            throw new Exception("Shipping method is not a home delivery method: {$shippingMethod->getType()->value}");
        }

        $this->shippingSettings->home_delivery_method = $shipping_method_id;
        $this->shippingSettings->save();
    }

    public function getLocalPickupStrategy(): AbstractShippingMethod
    {
        if (!Settings::get('shipping', 'local_pickup_enabled')) {
            throw new Exception("Local pickup is not enabled");
        }

        $shippingMethod = $this->shippingMethodManager->getShippingMethod(Settings::get('shipping', 'local_pickup_method'));

        if ($shippingMethod->getType() !== ShippingTypes::LOCAL_PICKUP) {
            throw new Exception("Shipping method is not a local pickup method: {$shippingMethod->getType()->value}");
        }

        return $shippingMethod;
    }

    public function getHomeDeliveryStrategy(): AbstractShippingMethod
    {
        if (!Settings::get('shipping', 'home_delivery_enabled')) {
            throw new Exception("Home delivery is not enabled");
        }

        $shippingMethod = $this->shippingMethodManager->getShippingMethod(Settings::get('shipping', 'home_delivery_method'));

        if ($shippingMethod->getType() !== ShippingTypes::HOME_DELIVERY) {
            throw new Exception("Shipping method is not a home delivery method: {$shippingMethod->getType()->value}");
        }

        return $shippingMethod;
    }
}
