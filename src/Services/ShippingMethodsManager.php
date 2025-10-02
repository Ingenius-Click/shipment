<?php

namespace Ingenius\Shipment\Services;

use Ingenius\Shipment\Exceptions\ShippingMethodAlreadyRegisteredException;
use Ingenius\Shipment\Exceptions\ShippingMethodNotActiveException;
use Ingenius\Shipment\Exceptions\ShippingMethodNotConfiguredException;
use Ingenius\Shipment\Exceptions\ShippingMethodNotFoundException;
use Ingenius\Shipment\ShippingMethods\AbstractShippingMethod;

class ShippingMethodsManager
{
    protected $shippingMethods = [];

    public function registerShippingMethod(string $shipping_method_id, string $shipping_method_class)
    {
        if (isset($this->shippingMethods[$shipping_method_id])) {
            throw new ShippingMethodAlreadyRegisteredException("Shipping method already registered: {$shipping_method_id}");
        }

        $this->shippingMethods[$shipping_method_id] = $shipping_method_class;
    }

    public function getShippingMethodNoQuestionAsk(string $shipping_method_id): AbstractShippingMethod
    {
        $shippingMethod = $this->shippingMethods[$shipping_method_id] ?? null;

        if (!$shippingMethod) {
            throw new ShippingMethodNotFoundException("Shipping method not found: {$shipping_method_id}");
        }

        return new $shippingMethod();
    }

    public function getAvailableShippingMethods(): array
    {
        $instances = array_map(function ($shippingMethod) {
            return new $shippingMethod();
        }, array_values($this->shippingMethods));

        $featureAccessibleShippingMethods = array_filter($instances, function ($shippingMethod) {
            return tenant() && tenant()->hasFeature($shippingMethod->getRequiredFeature()->getIdentifier());
        });

        return $featureAccessibleShippingMethods;
    }

    public function getShippingMethod(string $shipping_method_id, bool $ignoreIsConfigured = false): AbstractShippingMethod
    {
        $shippingMethod = $this->shippingMethods[$shipping_method_id] ?? null;

        if (!$shippingMethod) {
            throw new ShippingMethodNotFoundException("Shipping method not found: {$shipping_method_id}");
        }

        $shippingMethod = new $shippingMethod();

        if (!$shippingMethod->getActive()) {
            throw new ShippingMethodNotActiveException("Shipping method is not active: {$shipping_method_id}");
        }

        if (!tenant() || !tenant()->hasFeature($shippingMethod->getRequiredFeature()->getIdentifier())) {
            throw new ShippingMethodNotActiveException("Shipping method is not active: {$shipping_method_id}");
        }

        if (!$ignoreIsConfigured && !$shippingMethod->configured()) {
            throw new ShippingMethodNotConfiguredException("Shipping method is not configured: {$shipping_method_id}");
        }

        return $shippingMethod;
    }

    public function getActivesShippingMethods(string $type = ''): array
    {

        $instances = array_map(function ($shippingMethod) {
            return new $shippingMethod();
        }, array_values($this->shippingMethods));

        $featureAccessibleShippingMethods = array_filter($instances, function ($shippingMethod) {
            return tenant() && tenant()->hasFeature($shippingMethod->getRequiredFeature()->getIdentifier());
        });

        $activeShippingMethods = array_filter($featureAccessibleShippingMethods, function ($shippingMethod) {
            return $shippingMethod->getActive() && $shippingMethod->configured();
        });

        if ($type) {
            $activeShippingMethods = array_filter($activeShippingMethods, function ($shippingMethod) use ($type) {
                return $shippingMethod->getType()->value === $type;
            });
        }

        return array_values($activeShippingMethods);
    }
}
