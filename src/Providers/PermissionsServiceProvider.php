<?php

namespace Ingenius\Shipment\Providers;

use Illuminate\Support\ServiceProvider;
use Ingenius\Core\Support\PermissionsManager;
use Ingenius\Core\Traits\RegistersConfigurations;
use Ingenius\Shipment\Constants\ShippingMethodsPermissions;
use Ingenius\Shipment\Constants\ZonePermissions;

class PermissionsServiceProvider extends ServiceProvider {

    use RegistersConfigurations;

    /**
     * The package name.
     *
     * @var string
     */
    protected string $packageName = 'Shipment';

    /**
     * Boot the application events.
     */
    public function boot(PermissionsManager $permissionsManager): void
    {
        $this->registerPermissions($permissionsManager);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Register package-specific permission config
        $configPath = __DIR__ . '/../../config/permissions.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'shipment.permissions');
            $this->registerConfig($configPath, 'shipment.permissions', 'shipment');
        }
    }

    /**
     * Register the package's permissions.
     */
    protected function registerPermissions(PermissionsManager $permissionsManager): void
    {
        // Zone permissions
        $permissionsManager->register(
            ZonePermissions::INDEX,
            'View zones',
            $this->packageName,
            'tenant',
            __('shipment::permissions.display_names.view_zones'),
            __('shipment::permissions.groups.zones')
        );

        $permissionsManager->register(
            ZonePermissions::ACTIVATE,
            'Activate zones',
            $this->packageName,
            'tenant',
            __('shipment::permissions.display_names.activate_zones'),
            __('shipment::permissions.groups.zones')
        );

        // Shipping Methods permissions
        $permissionsManager->register(
            ShippingMethodsPermissions::INDEX,
            'View shipping methods',
            $this->packageName,
            'tenant',
            __('shipment::permissions.display_names.view_shipping_methods'),
            __('shipment::permissions.groups.shipping_methods')
        );

        $permissionsManager->register(
            ShippingMethodsPermissions::SHOW,
            'View shipping method',
            $this->packageName,
            'tenant',
            __('shipment::permissions.display_names.view_shipping_method'),
            __('shipment::permissions.groups.shipping_methods')
        );

        $permissionsManager->register(
            ShippingMethodsPermissions::CONFIGURE,
            'Configure shipping method',
            $this->packageName,
            'tenant',
            __('shipment::permissions.display_names.configure_shipping_method'),
            __('shipment::permissions.groups.shipping_methods')
        );
    }

}
