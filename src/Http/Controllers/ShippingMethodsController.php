<?php

namespace Ingenius\Shipment\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Shipment\Http\Requests\ConfigureShippingMethodRequest;
use Ingenius\Shipment\Http\Requests\SelectShippingMethodRequest;
use Ingenius\Shipment\Services\ShippingMethodsManager;
use Ingenius\Shipment\Services\ShippingStrategyManager;
use Ingenius\Shipment\Settings\ShippingSettings;

class ShippingMethodsController extends Controller
{
    use AuthorizesRequests;

    public function actives(ShippingMethodsManager $shippingMethodsManager): JsonResponse
    {
        $shippingMethods = $shippingMethodsManager->getActivesShippingMethods();

        return response()->api(message: 'Shipping methods fetched successfully', data: $shippingMethods);
    }


    public function configureShippingMethod(ConfigureShippingMethodRequest $request): JsonResponse
    {
        $shippingMethodsManager = app(ShippingMethodsManager::class);

        $shippingMethod = $shippingMethodsManager->getShippingMethod($request->shipping_method_id, true);

        $shippingMethod->setConfigData($request->except('shipping_method_id'));

        return response()->api(message: 'Shipping method configured successfully');
    }

    public function selectLocalPickupMethod(SelectShippingMethodRequest $request, ShippingStrategyManager $shippingStrategyManager): JsonResponse
    {
        $shippingStrategyManager->setLocalPickupMethod($request->shipping_method_id);

        return response()->api(message: 'Local pickup method selected successfully');
    }

    public function selectHomeDeliveryMethod(SelectShippingMethodRequest $request, ShippingStrategyManager $shippingStrategyManager): JsonResponse
    {
        $shippingStrategyManager->setHomeDeliveryMethod($request->shipping_method_id);

        return response()->api(message: 'Home delivery method selected successfully');
    }

    public function enableLocalPickup(): JsonResponse
    {
        $shippingSettings = app(ShippingSettings::class);

        $shippingSettings->local_pickup_enabled = true;
        $shippingSettings->save();

        return response()->api(message: 'Local pickup enabled successfully');
    }

    public function enableHomeDelivery(): JsonResponse
    {
        $shippingSettings = app(ShippingSettings::class);

        $shippingSettings->home_delivery_enabled = true;
        $shippingSettings->save();

        return response()->api(message: 'Home delivery enabled successfully');
    }
}
