<?php

namespace Ingenius\Shipment\Initializers;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Ingenius\Core\Interfaces\TenantInitializer;
use Ingenius\Core\Models\Tenant;
use Ingenius\Shipment\Actions\SeedCubanZonesAction;
use Ingenius\Shipment\Models\ShippingMethodData;
use Ingenius\Shipment\ShippingMethods\LocalPickupMethod;

class ShipmentTenantInitializer implements TenantInitializer
{
    public function initialize(Tenant $tenant, Command $command): void
    {
        $command->info('Setting up shipping methods...');

        if ($command->confirm('Do you want to enable local pickup shipping method?', true)) {

            $pickupAddress = $command->ask('Enter the pickup address');

            $shippingMethodData = $this->createLocalPickupShippingMethod($tenant);

            $shippingMethodData->calculation_data = [
                'pickup_address' => $pickupAddress,
            ];

            $shippingMethodData->save();

            $this->activateLocalPickupShippingMethod($shippingMethodData);
        }

        $command->info('Seeding shipping zones...');
        $this->seedZones($tenant);
    }

    public function initializeViaRequest(Tenant $tenant, Request $request): void
    {
        if ($request->enable_local_pickup) {
            $pickupAddress = $request->pickup_address;

            $shippingMethodData = $this->createLocalPickupShippingMethod($tenant);

            $shippingMethodData->calculation_data = [
                'pickup_address' => $pickupAddress,
            ];

            $shippingMethodData->save();

            $this->activateLocalPickupShippingMethod($shippingMethodData);
        }

        $this->seedZones($tenant);
    }

    protected function activateLocalPickupShippingMethod(ShippingMethodData $shippingMethodData): void
    {
        $shippingMethodData->active = true;
        $shippingMethodData->save();
    }

    protected function createLocalPickupShippingMethod(Tenant $tenant): ShippingMethodData
    {
        $shippingMethod = ShippingMethodData::where('shipping_method_id', 'local_pickup')->first();

        if (!$shippingMethod) {
            try {
                new LocalPickupMethod();
                $shippingMethod = ShippingMethodData::where('shipping_method_id', 'local_pickup')->first();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $shippingMethod;
    }

    protected function seedZones(Tenant $tenant): void
    {
        $action = app(SeedCubanZonesAction::class);

        $action->handle();
    }

    public function rules(): array
    {
        $rules = [
            'enable_local_pickup' => 'required|boolean',
        ];

        $localPickupMethod = new LocalPickupMethod();

        $localPickupRules = $localPickupMethod->configDataRules();

        //replace for each rule in the value all required with required_if:enable_local_pickup,true
        foreach ($localPickupRules as $key => $rule) {
            if (str_contains($rule, 'required')) {
                $localPickupRules[$key] = str_replace('required', 'required_if:enable_local_pickup,true', $rule);
            }
        }

        $rules = array_merge($rules, $localPickupRules);

        return $rules;
    }

    public function getPriority(): int
    {
        return 90;
    }

    public function getName(): string
    {
        return 'Shipping Methods Setup';
    }

    public function getPackageName(): string
    {
        return 'shipment';
    }
}
