<?php

namespace Ingenius\Shipment\Providers;

use Illuminate\Support\ServiceProvider;
use Ingenius\Core\Support\PermissionsManager;
use Ingenius\Core\Traits\RegistersConfigurations;
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
        // Register Products package permissions
        $permissionsManager->registerMany([
            ZonePermissions::INDEX => 'View zones',
            ZonePermissions::ACTIVATE => 'Activate zones',
        ], $this->packageName, 'tenant');
    }

}