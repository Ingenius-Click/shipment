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

    public function configFormDataSchema(): array {
        return [
            'zones' => [
                'label' => __('Shipping Zones'),
                'type' => 'zone-cost-table',
                'rules' => $this->configDataRules()['zones'],
                'description' => __('Configure shipping costs for each province and municipality. Set costs at the province level to apply to all municipalities, or set individual municipality costs to override.'),
                'group' => 'zones',
                'order' => 1,
                'fields' => [
                    'id' => [
                        'label' => __('Zone ID'),
                        'type' => 'hidden',
                        'rules' => $this->configDataRules()['zones.*.id'],
                    ],
                    'name' => [
                        'label' => __('Zone Name'),
                        'type' => 'text',
                        'rules' => $this->configDataRules()['zones.*.name'],
                        'disabled' => true,
                    ],
                    'cost' => [
                        'label' => __('Shipping Cost'),
                        'type' => 'number',
                        'rules' => $this->configDataRules()['zones.*.cost'],
                        'placeholder' => __('Enter cost'),
                        'description' => __('Leave empty to inherit from parent zone'),
                        'attributes' => [
                            'min' => 0,
                            'step' => '0.01',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function renderFormData(): array {
        // Get active municipalities
        $activeMunicipalities = Zone::active()->municipalities()->with('parent')->get();

        // Get provinces that have at least one active municipality
        $provincesWithActiveMunicipalities = $activeMunicipalities
            ->pluck('parent')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->map(function ($province) {
                return [
                    'label' => $province->name,
                    'value' => $province->name,
                ];
            })
            ->values();

        // Get all active municipalities grouped by province
        $municipalitiesOptions = $activeMunicipalities
            ->sortBy('name')
            ->map(function ($municipality) {
                return [
                    'label' => $municipality->name,
                    'value' => $municipality->name,
                    'province' => $municipality->parent?->name,
                ];
            })
            ->values();

        return [
            [
                'field' => 'province',
                'type' => 'select',
                'label' => __('Province'),
                'placeholder' => __('Select province'),
                'required' => true,
                'disabled' => false,
                'options' => $provincesWithActiveMunicipalities->toArray(),
            ],
            [
                'field' => 'municipality',
                'type' => 'select',
                'label' => __('Municipality'),
                'placeholder' => __('Select municipality'),
                'required' => true,
                'disabled' => false,
                'options' => $municipalitiesOptions->toArray(),
                'options_modifiers' => [
                    [
                        'type' => 'filter',
                        'field' => 'province',
                        'operator' => '=',
                        'value_field' => 'province',
                    ]
                ],
                'conditions' => [
                    [
                        'value_field' => 'province',
                        'operator' => '!=',
                        'value' => null,
                    ]
                ],
            ]
        ];
    }
} 