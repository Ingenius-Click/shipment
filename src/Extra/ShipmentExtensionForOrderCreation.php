<?php

namespace Ingenius\Shipment\Extra;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ingenius\Orders\Extensions\BaseOrderExtension;
use Ingenius\Orders\Models\Order;
use Ingenius\Shipment\Enums\ShippingTypes;
use Ingenius\Shipment\Models\Shipment;
use Ingenius\Shipment\Services\ShippingStrategyManager;

class ShipmentExtensionForOrderCreation extends BaseOrderExtension
{
    public function __construct(
        protected ShippingStrategyManager $shippingStrategyManager
    ) {}

    public function getValidationRules(Request $request): array
    {
        $rules = [
            'shipping_type' => 'required|string|in:' . implode(',', [ShippingTypes::LOCAL_PICKUP->value, ShippingTypes::HOME_DELIVERY->value]),
            'beneficiary_name' => 'required|string',
            'beneficiary_email' => 'required|email',
            'beneficiary_city' => 'nullable|string',
            'beneficiary_state' => 'nullable|string',
            'beneficiary_zip' => 'nullable|string',
            'beneficiary_country' => 'nullable|string',
            'beneficiary_phone' => 'required|string',
        ];

        $method = null;

        if ($request->shipping_type && $request->shipping_type === ShippingTypes::HOME_DELIVERY->value) {
            $method = $this->shippingStrategyManager->getHomeDeliveryStrategy();
            $rules = array_merge($rules, [
                'beneficiary_address' => 'required|string',
            ]);
        } else if ($request->shipping_type && $request->shipping_type === ShippingTypes::LOCAL_PICKUP->value) {
            $method = $this->shippingStrategyManager->getLocalPickupStrategy();
            $rules = array_merge($rules, [
                'pickup_address' => 'required|string',
            ]);
        }

        if ($method) {
            $rules = array_merge($rules, $method->rules());
        }

        return $rules;
    }

    public function processOrder(Order $order, array $validatedData, array &$context): array
    {
        //Obtener el shipping_type
        $shippingType = $validatedData['shipping_type'];

        //Dependiendo del shipping_type, obtener el método de envío
        $method = null;

        if ($shippingType === ShippingTypes::HOME_DELIVERY->value) {
            $method = $this->shippingStrategyManager->getHomeDeliveryStrategy();
        } else if ($shippingType === ShippingTypes::LOCAL_PICKUP->value) {
            $method = $this->shippingStrategyManager->getLocalPickupStrategy();
        }

        //Calcular el costo del envío
        $calculationData = $method->calculate($validatedData);

        //Crear el envío
        $shipment = Shipment::create([
            'shippable_id' => $order->id,
            'shippable_type' => Order::class,
            'tracking_number' => Str::random(10),
            'shipping_method_id' => $method->getId(),
            'beneficiary_name' => $validatedData['beneficiary_name'],
            'beneficiary_email' => $validatedData['beneficiary_email'],
            'beneficiary_address' => $method->getType() === ShippingTypes::HOME_DELIVERY ? $validatedData['beneficiary_address'] : null,
            'beneficiary_city' => $validatedData['beneficiary_city'] ?? null,
            'beneficiary_state' => $validatedData['beneficiary_state'] ?? null,
            'beneficiary_zip' => $validatedData['beneficiary_zip'] ?? null,
            'beneficiary_country' => $validatedData['beneficiary_country'] ?? null,
            'beneficiary_phone' => $validatedData['beneficiary_phone'],
            'pickup_address' => $method->getType() === ShippingTypes::LOCAL_PICKUP ? $validatedData['pickup_address'] : null,
            'base_currency_code' => $calculationData->base_currency_code,
            'currency_code' => $order->getCurrency(),
            'exchange_rate' => $order->getExchangeRate(),
            'base_amount' => $calculationData->price
        ]);

        return [
            'amount' => $calculationData->price,
            'base_currency_code' => $calculationData->base_currency_code,
            'beneficiary_name' => $shipment->beneficiary_name,
            'beneficiary_email' => $shipment->beneficiary_email,
            'beneficiary_address' => $shipment->beneficiary_address,
            'beneficiary_city' => $shipment->beneficiary_city,
            'beneficiary_state' => $shipment->beneficiary_state,
            'beneficiary_zip' => $shipment->beneficiary_zip,
            'beneficiary_country' => $shipment->beneficiary_country,
            'beneficiary_phone' => $shipment->beneficiary_phone,
            'pickup_address' => $shipment->pickup_address,
        ];
    }

    public function calculateSubtotal(Order $order, float $currentSubtotal, array &$context): float
    {
        $orderClass = get_class($order);
        $shipment = Shipment::where('shippable_id', $order->id)->where('shippable_type', $orderClass)->first();

        if ($shipment) {
            return $shipment->base_amount;
        }

        return $currentSubtotal;
    }

    public function extendOrderArray(Order $order, array $orderArray): array
    {
        $orderClass = get_class($order);
        $shipment = Shipment::where('shippable_id', $order->id)->where('shippable_type', $orderClass)->first();

        $orderArray['shipment'] = [
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'price' => $shipment->base_amount,
            'shipping_method_id' => $shipment->shipping_method_id,
            'beneficiary_name' => $shipment->beneficiary_name,
            'beneficiary_email' => $shipment->beneficiary_email,
            'beneficiary_address' => $shipment->beneficiary_address,
            'pickup_address' => $shipment->pickup_address,
            'beneficiary_city' => $shipment->beneficiary_city,
            'beneficiary_state' => $shipment->beneficiary_state,
            'beneficiary_zip' => $shipment->beneficiary_zip,
            'beneficiary_country' => $shipment->beneficiary_country,
            'beneficiary_phone' => $shipment->beneficiary_phone,
        ];

        return $orderArray;
    }

    public function getPriority(): int
    {
        return 95;
    }

    public function getName(): string
    {
        return 'ShipmentProcessor';
    }
}
