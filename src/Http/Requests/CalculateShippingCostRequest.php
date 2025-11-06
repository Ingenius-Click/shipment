<?php

namespace Ingenius\Shipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ingenius\Shipment\ShippingMethods\AbstractShippingMethod;

class CalculateShippingCostRequest extends FormRequest
{
    protected AbstractShippingMethod|null $shippingMethod = null;
    private $shippingStrategyManager;

    public function __construct() {
        parent::__construct();
        $this->shippingStrategyManager = app(\Ingenius\Shipment\Services\ShippingStrategyManager::class);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array {

        return [
            'shipping_type' => 'required|string',
            ... $this->shipping_type ? $this->getConfiguredShippingRules($this->shipping_type) : []
        ];

    }

    public function getConfiguredShippingRules(string $shippingType): array {
        
        if($shippingType === \Ingenius\Shipment\Enums\ShippingTypes::HOME_DELIVERY->value) {
            $this->shippingMethod = $this->shippingStrategyManager->getHomeDeliveryStrategy();
        } else if($shippingType === \Ingenius\Shipment\Enums\ShippingTypes::LOCAL_PICKUP->value) {
            $this->shippingMethod = $this->shippingStrategyManager->getLocalPickupStrategy();
        } else {
            return [];
        }

        return $this->shippingMethod->rules();
    }

    public function getShippingMethodInstance(): AbstractShippingMethod|null {
        return $this->shippingMethod;
    }
}