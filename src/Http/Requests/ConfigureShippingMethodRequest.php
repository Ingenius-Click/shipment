<?php

namespace Ingenius\Shipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ingenius\Shipment\Services\ShippingMethodsManager;

class ConfigureShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->shipping_method_id ? $this->getRules() : [],
        ];
    }

    protected function getRules(): array
    {

        $shippingMethodsManager = app(ShippingMethodsManager::class);

        $shippingMethod = $shippingMethodsManager->getShippingMethod($this->shipping_method_id, true);

        return $shippingMethod->configDataRules();
    }
}
