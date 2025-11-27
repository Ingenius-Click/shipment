<?php

namespace Ingenius\Shipment\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Ingenius\Core\Helpers\AuthHelper;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Core\Services\PackageHookManager;
use Ingenius\Shipment\Constants\ShippingMethodsPermissions;
use Ingenius\Shipment\Exceptions\ShippingMethodNotActiveException;
use Ingenius\Shipment\Http\Requests\CalculateShippingCostRequest;
use Ingenius\Shipment\Http\Requests\ConfigureShippingMethodRequest;
use Ingenius\Shipment\Http\Requests\SelectShippingMethodRequest;
use Ingenius\Shipment\Services\ShippingMethodsManager;
use Ingenius\Shipment\Services\ShippingStrategyManager;
use Ingenius\Shipment\Settings\ShippingSettings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShippingMethodsController extends Controller
{
    use AuthorizesRequests;

    public function actives(Request $request, ShippingMethodsManager $shippingMethodsManager): JsonResponse
    {
        $shippingTypes = implode(',', array_map(fn($type) => $type->value, \Ingenius\Shipment\Enums\ShippingTypes::cases()));

        $validated = $request->validate([
            'type' => "nullable|in:$shippingTypes"
        ]);

        $shippingMethods = $shippingMethodsManager->getActivesShippingMethods($validated['type'] ?? '');

        return Response::api(message: 'Shipping methods fetched successfully', data: $shippingMethods);
    }

    public function index(Request $request, ShippingMethodsManager $shippingMethodsManager): JsonResponse 
    {
        AuthHelper::checkPermission(ShippingMethodsPermissions::INDEX);

        $shippingMethods = $shippingMethodsManager->getAvailableShippingMethods();

        return Response::api(message: 'Shipping methods fetched successfully', data: $shippingMethods);
    }

    public function show(Request $request, string $shipping_method_id, ShippingMethodsManager $shippingMethodsManager): JsonResponse
    {
        AuthHelper::checkPermission(ShippingMethodsPermissions::SHOW);

        $shippingMethod = $shippingMethodsManager->getShippingMethod($shipping_method_id, true);

        return Response::api(data: [
            'id' => $shippingMethod->getId(),
            'name' => $shippingMethod->getName(),
            'config_data_rules' => $shippingMethod->configDataRules(),
            'config_data' => $shippingMethod->getConfigData(),
            'config_external_data' => $shippingMethod->configExternalData(),
        ], message: 'Shipping method fetched sucessfully');
    }

    public function configureShippingMethod(ConfigureShippingMethodRequest $request, string $shipping_method_id): JsonResponse
    {
        AuthHelper::checkPermission(ShippingMethodsPermissions::CONFIGURE);

        $shippingMethodsManager = app(ShippingMethodsManager::class);

        $shippingMethod = $shippingMethodsManager->getShippingMethod($shipping_method_id, true);

        $shippingMethod->setConfigData($request->except('shipping_method_id'));

        return Response::api(message: 'Shipping method configured successfully');
    }

    public function selectLocalPickupMethod(SelectShippingMethodRequest $request, ShippingStrategyManager $shippingStrategyManager): JsonResponse
    {
        AuthHelper::checkPermission(ShippingMethodsPermissions::CONFIGURE);

        $shippingStrategyManager->setLocalPickupMethod($request->shipping_method_id);

        return Response::api(message: 'Local pickup method selected successfully');
    }

    public function selectHomeDeliveryMethod(SelectShippingMethodRequest $request, ShippingStrategyManager $shippingStrategyManager): JsonResponse
    {
        AuthHelper::checkPermission(ShippingMethodsPermissions::CONFIGURE);

        $shippingStrategyManager->setHomeDeliveryMethod($request->shipping_method_id);

        return Response::api(message: 'Home delivery method selected successfully');
    }

    public function enableLocalPickup(): JsonResponse
    {
        AuthHelper::checkPermission(ShippingMethodsPermissions::CONFIGURE);

        $shippingSettings = app(ShippingSettings::class);

        $shippingSettings->local_pickup_enabled = true;
        $shippingSettings->save();

        return Response::api(message: 'Local pickup enabled successfully');
    }

    public function enableHomeDelivery(): JsonResponse
    {
        AuthHelper::checkPermission(ShippingMethodsPermissions::CONFIGURE);

        $shippingSettings = app(ShippingSettings::class);

        $shippingSettings->home_delivery_enabled = true;
        $shippingSettings->save();

        return Response::api(message: 'Home delivery enabled successfully');
    }

    public function calculateShippingCost(CalculateShippingCostRequest $request): JsonResponse {
        $method = $request->getShippingMethodInstance();

        if(!$method) {
            throw new NotFoundHttpException();
        }

        // Calculate cost in base currency
        $cost = $method->calculate($request->validated());

        // Execute hook - may apply discounts to shipping cost
        $hookManager = app(PackageHookManager::class);
        $data = $hookManager->execute('shipping.cost.calculated', [], [
            'shipping_method' => $method,
            'calculated_cost' => $cost,
            'request_data' => $request->validated(),
        ]);

        // Add currency metadata to data
        $data['currency'] = get_currency_metadata();

        return Response::api(message: 'Shipping cost calculated successfully', data: [
            'shipping_cost' => $cost,  // Keep base cost for calculations
            'shipping_cost_data' => $data
        ]);
    }
}
