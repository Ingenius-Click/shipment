<?php

namespace Ingenius\Shipment\Extra;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ingenius\Orders\Extensions\BaseOrderExtension;
use Ingenius\Orders\Models\Order;
use Ingenius\Shipment\Enums\ShippingTypes;
use Ingenius\Shipment\Models\Address;
use Ingenius\Shipment\Models\Beneficiary;
use Ingenius\Shipment\Models\Shipment;
use Ingenius\Shipment\Rules\AddressBelongsToUser;
use Ingenius\Shipment\Rules\BeneficiaryBelongsToUser;
use Ingenius\Shipment\Services\ShippingStrategyManager;
use Ingenius\Discounts\Models\DiscountUsage;
use Ingenius\Discounts\Models\DiscountCampaign;

class ShipmentExtensionForOrderCreation extends BaseOrderExtension
{
    public function __construct(
        protected ShippingStrategyManager $shippingStrategyManager
    ) {}

    public function getValidationRules(Request $request): array
    {
        $rules = [
            'shipping_type' => 'required|string|in:' . implode(',', [ShippingTypes::LOCAL_PICKUP->value, ShippingTypes::HOME_DELIVERY->value]),
            'beneficiary_id' => ['nullable', 'integer', 'exists:beneficiaries,id', new BeneficiaryBelongsToUser()],
            'beneficiary_name' => 'required_without:beneficiary_id|string',
            'beneficiary_email' => 'required_without:beneficiary_id|email',
            'beneficiary_city' => 'nullable|string',
            'beneficiary_state' => 'nullable|string',
            'beneficiary_zip' => 'nullable|string',
            'beneficiary_country' => 'nullable|string',
            'beneficiary_phone' => 'required_without:beneficiary_id|string',
        ];

        $method = null;

        if ($request->shipping_type && $request->shipping_type === ShippingTypes::HOME_DELIVERY->value) {
            $method = $this->shippingStrategyManager->getHomeDeliveryStrategy();
            $rules = array_merge($rules, [
                'address_id' => ['nullable', 'integer', 'exists:addresses,id', new AddressBelongsToUser()],
                'beneficiary_address' => 'required_without:address_id|string',
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
        // If beneficiary_id is provided, load the beneficiary data
        if (!empty($validatedData['beneficiary_id'])) {
            $beneficiary = Beneficiary::find($validatedData['beneficiary_id']);
            if ($beneficiary) {
                // Copy beneficiary data to validated data
                $validatedData['beneficiary_name'] = $beneficiary->name;
                $validatedData['beneficiary_email'] = $beneficiary->email;
                $validatedData['beneficiary_phone'] = $beneficiary->phone;
            }
        }

        // If address_id is provided, load the address data
        if (!empty($validatedData['address_id'])) {
            $address = Address::find($validatedData['address_id']);
            if ($address) {
                // Copy address data to beneficiary_address field
                // Format: address, municipality, province
                $addressParts = [
                    $address->address,
                    $address->municipality,
                    $address->province,
                ];
                $validatedData['beneficiary_address'] = implode(', ', array_filter($addressParts));
            }
        }

        // Get shipping type and method
        $shippingType = $validatedData['shipping_type'];
        $method = null;

        if ($shippingType === ShippingTypes::HOME_DELIVERY->value) {
            $method = $this->shippingStrategyManager->getHomeDeliveryStrategy();
        } else if ($shippingType === ShippingTypes::LOCAL_PICKUP->value) {
            $method = $this->shippingStrategyManager->getLocalPickupStrategy();
        }

        // Calculate shipping cost
        $calculationData = $method->calculate($validatedData);
        $originalPrice = $calculationData->price;

        // Calculate shipping discount if any
        $shippingDiscountResult = $this->calculateShippingDiscount($originalPrice, $context);
        $realPrice = $originalPrice - $shippingDiscountResult['total_discount'];

        $data = [
            'calculation_data' => $calculationData,
            'shipping_discounts' => $shippingDiscountResult['applied_discounts'],
        ];

        // Create shipment
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
            'base_amount' => $realPrice,
            'data' => $data
        ]);

        // Register shipping discount usages
        foreach ($shippingDiscountResult['applied_discounts'] as $discount) {
            DiscountUsage::create([
                'campaign_id' => $discount['campaign_id'],
                'customer_id' => $order->userable_id,
                'orderable_id' => $order->id,
                'orderable_type' => get_class($order),
                'discount_amount_applied' => $discount['amount_saved'],
                'used_at' => now(),
                'metadata' => [
                    'campaign_name' => $discount['campaign_name'],
                    'discount_type' => $discount['discount_type'],
                    'shipment_id' => $shipment->id,
                    'original_shipping_cost' => $originalPrice,
                    'affected_items' => [],
                ],
            ]);

            // Increment campaign usage counter
            $campaign = DiscountCampaign::find($discount['campaign_id']);
            if ($campaign) {
                $campaign->increment('current_uses');
            }
        }

        // Add shipping cost to total (after discounts)
        $context['total'] = ($context['total'] ?? 0) + $realPrice;

        return [
            'amount' => $originalPrice,
            'discounted_amount' => $realPrice,
            'discount_applied' => $shippingDiscountResult['total_discount'],
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

    /**
     * Calculate shipping discount based on discounts in context
     *
     * @param int $shippingCost Original shipping cost in cents
     * @param array $context Order context with shipping_discounts
     * @return array ['total_discount' => int, 'applied_discounts' => array]
     */
    protected function calculateShippingDiscount(int $shippingCost, array $context): array
    {
        $shippingDiscounts = $context['shipping_discounts'] ?? [];
        $appliedDiscounts = [];
        $totalDiscount = 0;
        $remainingCost = $shippingCost;

        foreach ($shippingDiscounts as $discount) {
            if ($remainingCost <= 0) {
                break;
            }

            $discountType = $discount['discount_type'] ?? null;
            $discountValue = $discount['discount_value'] ?? 0;
            $amountSaved = 0;

            if ($discountType === 'percentage') {
                // Percentage discount (e.g., 100 = 100% = free shipping)
                $amountSaved = (int) floor($remainingCost * ($discountValue / 100));
            } elseif ($discountType === 'fixed_amount') {
                // Fixed amount discount (e.g., 500 = $5 off)
                $amountSaved = min($discountValue, $remainingCost);
            }

            if ($amountSaved > 0) {
                $totalDiscount += $amountSaved;
                $remainingCost -= $amountSaved;

                $appliedDiscounts[] = [
                    'campaign_id' => $discount['campaign_id'],
                    'campaign_name' => $discount['campaign_name'],
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'amount_saved' => $amountSaved,
                ];
            }
        }

        return [
            'total_discount' => $totalDiscount,
            'applied_discounts' => $appliedDiscounts,
        ];
    }

    public function calculateSubtotal(Order $order, float $currentSubtotal, array &$context): float
    {
        $orderClass = get_class($order);
        $shipment = Shipment::where('shippable_id', $order->id)->where('shippable_type', $orderClass)->first();

        if ($shipment) {
            return $currentSubtotal + $shipment->base_amount;
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
