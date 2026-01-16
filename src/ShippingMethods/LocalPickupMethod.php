<?php

namespace Ingenius\Shipment\ShippingMethods;

use Illuminate\Validation\Rule;
use Ingenius\Core\Interfaces\FeatureInterface;
use Ingenius\Shipment\Enums\ShippingTypes;
use Ingenius\Shipment\Features\LocalPickupMethodFeature;
use Ingenius\Shipment\ShippingMethods\Response\CalculationResponse;

class LocalPickupMethod extends AbstractShippingMethod
{
    protected $id = 'local_pickup';

    protected $name = 'Local Pickup';

    public function getName(): string
    {
        return __($this->name);
    }

    public function getRequiredFeature(): FeatureInterface
    {
        return new LocalPickupMethodFeature();
    }

    public function getType(): ShippingTypes
    {
        return ShippingTypes::LOCAL_PICKUP;
    }

    public function calculate(array $data): CalculationResponse
    {
        return new CalculationResponse(0, 'USD');
    }

    public function rules(): array
    {
        $pickupAddress = $this->getConfigData()['pickup_address'];

        return [
            'pickup_address' => ['required', 'string', Rule::in([$pickupAddress])],
        ];
    }

    public function configDataRules(): array
    {
        return [
            'pickup_address' => 'required|string',
        ];
    }

    public function configFormDataSchema(): array
    {
        return [
            'pickup_address' => [
                'label' => __('Pickup Address'),
                'type' => 'text',
                'rules' => $this->configDataRules()['pickup_address'],
                'description' => __('The address where customers can pick up their orders.'),
                'placeholder' => __('Enter the pickup address'),
                'group' => 'location',
                'order' => 1,
            ],
        ];
    }

    public function renderFormData(): array
    {
        return [
            [
                'field' => 'pickup_address',
                'type' => 'text',
                'label' => __('Pickup Address'),
                'placeholder' => __('Enter pickup address'),
                'required' => true,
                'value' => $this->getConfigData()['pickup_address'],
                'disabled' => true,
            ],
        ];
    }
}
