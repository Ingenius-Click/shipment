<?php

namespace Ingenius\Shipment\ShippingMethods;

use Ingenius\Shipment\Enums\ShippingTypes;
use Ingenius\Shipment\Exceptions\ShippingCalculationException;
use Ingenius\Shipment\Features\ProvinceAndMunicipalityMethodFeature;
use Ingenius\Shipment\Models\Zone;

class ProvinceAndMunicipalityShippingMethod extends AbstractShippingMethod
{
    protected $id = 'province_and_municipality';

    protected $name = 'Province and Municipality';

    public function getName(): string {
        return __($this->name);
    }

    public function getRequiredFeature(): \Ingenius\Core\Interfaces\FeatureInterface {
        return new ProvinceAndMunicipalityMethodFeature();
    }

    public function getType(): \Ingenius\Shipment\Enums\ShippingTypes {
        return ShippingTypes::HOME_DELIVERY;
    }

    public function calculate(array $data): Response\CalculationResponse {
        $zones = $this->getConfigData()['zones'] ?? [];

        $munValue = array_find($zones, function ($zone) use ($data) {
            return $zone['name'] == $data['municipality'];
        });

        if ($munValue && isset($munValue['cost'])) {
            return new Response\CalculationResponse($munValue['cost'] * 100, 'USD');
        }

        $provValue = array_find($zones, function ($zone) use ($data) {
            return $zone['name'] == $data['province'];
        });

        if ($provValue) {
            return new Response\CalculationResponse($provValue['cost'] * 100, 'USD');
        }

        throw new ShippingCalculationException(__('No shipping rate found for the given province and municipality.'));
    }

    public function rules(): array {

        return [
            'province' => 'required|string|zone_configuration_valid',
            'municipality' => 'required|string|zone_configuration_valid',
        ];
    }

    public function configDataRules(): array {
        return [
            'zones' => 'required|array',
            'zones.*.id' => 'required|numeric|exists:zones,id',
            'zones.*.name' => 'required|string|exists:zones,name',
            'zones.*.cost' => 'nullable|numeric|min:0|zone_cost_required_if_parent_has_no_cost',
        ];
    }

    public function configExternalData(): array {
        $activeZones = Zone::active()->with('parent')->orderBy('id')->get();

        $zones = $activeZones->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'parent_id' => $zone->parent_id,
            ];
        });

        // Include parents of active zones even if they're not active
        $parents = $activeZones->filter(fn($zone) => $zone->parent_id !== null)
            ->pluck('parent')
            ->filter()
            ->unique('id')
            ->map(function ($parent) {
                return [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'parent_id' => $parent->parent_id,
                ];
            });

        return [
            'zones' => $zones->merge($parents)->unique('id')->sortBy('id')->values()->toArray(),
        ];
    }

    public function renderFormData(): array {
        return [
            [
                'field' => 'province',
                'type' => 'text',
                'label' => __('Province'),
                'placeholder' => __('Enter province'),
                'required' => true,
                'disabled' => false,
            ],
            [
                'field' => 'municipality',
                'type' => 'text',
                'label' => __('Municipality'),
                'placeholder' => __('Enter municipality'),
                'required' => true,
                'disabled' => false,
            ],
        ];
    }
} 