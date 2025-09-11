<?php

namespace Ingenius\Shipment\Providers;

use Illuminate\Support\ServiceProvider;
use Ingenius\Core\Services\FeatureManager;
use Ingenius\Core\Support\TenantInitializationManager;
use Ingenius\Core\Traits\RegistersMigrations;
use Ingenius\Core\Traits\RegistersConfigurations;
use Ingenius\Orders\Services\OrderExtensionManager;
use Ingenius\Orders\Services\InvoiceDataManager;
use Ingenius\Shipment\Extra\ShipmentExtensionForOrderCreation;
use Ingenius\Shipment\InvoiceData\ShipmentInvoiceDataProvider;
use Ingenius\Shipment\Features\ConfigureShippingMethodFeature;
use Ingenius\Shipment\Features\EnableHomeDeliveryFeature;
use Ingenius\Shipment\Features\EnableLocalPickupFeature;
use Ingenius\Shipment\Features\ListShippingMethodsFeature;
use Ingenius\Shipment\Features\LocalPickupMethodFeature;
use Ingenius\Shipment\Features\SelectHomeDeliveryMethodFeature;
use Ingenius\Shipment\Features\SelectLocalPickupMethodFeature;
use Ingenius\Shipment\Initializers\ShipmentTenantInitializer;
use Ingenius\Shipment\Services\ShippingMethodsManager;
use Ingenius\Shipment\Services\ShippingStrategyManager;
use Ingenius\Shipment\ShippingMethods\LocalPickupMethod;

class ShipmentServiceProvider extends ServiceProvider
{
    use RegistersMigrations, RegistersConfigurations;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/shipment.php', 'shipment');

        // Register configuration with the registry
        $this->registerConfig(__DIR__ . '/../../config/shipment.php', 'shipment', 'shipment');

        // Register the route service provider
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(ShippingMethodsManager::class, function () {
            return new ShippingMethodsManager();
        });

        $this->registerShippingMethods();

        $this->app->singleton(ShippingStrategyManager::class, function () {
            return new ShippingStrategyManager($this->app->make(ShippingMethodsManager::class));
        });

        $this->app->afterResolving(FeatureManager::class, function (FeatureManager $manager) {
            $manager->register(new ListShippingMethodsFeature());
            $manager->register(new LocalPickupMethodFeature());
            $manager->register(new ConfigureShippingMethodFeature());
            $manager->register(new SelectLocalPickupMethodFeature());
            $manager->register(new SelectHomeDeliveryMethodFeature());
            $manager->register(new EnableLocalPickupFeature());
            $manager->register(new EnableHomeDeliveryFeature());
        });

        // Register the order extension
        $this->app->afterResolving(OrderExtensionManager::class, function (OrderExtensionManager $manager) {
            $manager->register(new ShipmentExtensionForOrderCreation($this->app->make(ShippingStrategyManager::class)));
        });

        // Register the invoice data provider
        $this->app->afterResolving(InvoiceDataManager::class, function (InvoiceDataManager $manager) {
            $manager->register(new ShipmentInvoiceDataProvider($this->app->make(ShippingMethodsManager::class)));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register translations
        $this->registerTranslations();

        // Register migrations with the registry
        $this->registerMigrations(__DIR__ . '/../../database/migrations', 'shipment');

        // Check if there's a tenant migrations directory and register it
        $tenantMigrationsPath = __DIR__ . '/../../database/migrations/tenant';
        if (is_dir($tenantMigrationsPath)) {
            $this->registerTenantMigrations($tenantMigrationsPath, 'shipment');
        }

        // Load views only if they exist
        $viewsPath = __DIR__ . '/../../resources/views';
        if (is_dir($viewsPath) && count(glob($viewsPath . '/*.blade.php')) > 0) {
            $this->loadViewsFrom($viewsPath, 'shipment');
            
            // Publish views only if they exist
            $this->publishes([
                $viewsPath => resource_path('views/vendor/shipment'),
            ], 'shipment-views');
        }

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'shipment');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/shipment.php' => config_path('shipment.php'),
        ], 'shipment-config');

        // Publish translations
        $this->publishes([
            __DIR__ . '/../../resources/lang' => lang_path('vendor/shipment'),
        ], 'shipment-translations');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'shipment-migrations');

        // Register tenant initializer
        $this->registerTenantInitializer();
    }

    public function registerShippingMethods(): void
    {
        $this->app->afterResolving(ShippingMethodsManager::class, function (ShippingMethodsManager $manager) {
            $manager->registerShippingMethod('local_pickup', LocalPickupMethod::class);
        });
    }

    /**
     * Register tenant initializer
     */
    protected function registerTenantInitializer(): void
    {
        $this->app->afterResolving(TenantInitializationManager::class, function (TenantInitializationManager $manager) {
            $initializer = $this->app->make(ShipmentTenantInitializer::class);
            $manager->register($initializer);
        });
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'shipment');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../resources/lang', 'shipment');
    }
}
