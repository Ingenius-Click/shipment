<?php

namespace Ingenius\Shipment\Initializers;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Ingenius\Core\Interfaces\TenantInitializer;
use Ingenius\Core\Models\Tenant;
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

            $shippingMethodData->setConfigData([
                'pickup_address' => $pickupAddress,
            ]);

            $this->activateLocalPickupShippingMethod($shippingMethodData);
        }
    }

    public function initializeViaRequest(Tenant $tenant, Request $request): void
    {
        if ($request->enable_local_pickup) {
            $pickupAddress = $request->pickup_address;

            $shippingMethodData = $this->createLocalPickupShippingMethod($tenant);

            $shippingMethodData->setConfigData([
                'pickup_address' => $pickupAddress,
            ]);

            $this->activateLocalPickupShippingMethod($shippingMethodData);
        }
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

    public function rules(): array
    {
        $rules = [
            'enable_local_pickup' => 'required|boolean',
        ];

        $localPickupMethod = new LocalPickupMethod();
        $rules = array_merge($rules, $localPickupMethod->configDataRules());

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
