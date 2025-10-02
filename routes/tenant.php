<?php

use Illuminate\Support\Facades\Route;
use Ingenius\Shipment\Http\Controllers\ShippingMethodsController;
use Ingenius\Shipment\Http\Controllers\ZoneController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here is where you can register tenant-specific routes for your package.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the tenant middleware for multi-tenancy support.
|
*/

// Route::get('tenant-example', function () {
//     return 'Hello from tenant-specific route! Current tenant: ' . tenant('id');
// });

Route::middleware([
    'api',
])->prefix('api')->group(function () {
    Route::prefix('shipment')->group(function () {
        Route::get('shipping-methods/actives', [ShippingMethodsController::class, 'actives'])->name('shipping-methods.actives')->middleware('tenant.has.feature:list-shipping-methods');

        Route::middleware('tenant.user')->group(function () {
            Route::get('shipping-methods', [ShippingMethodsController::class, 'index'])->name('shipping-methods.index')->middleware('tenant.has.feature:list-shipping-methods');
            Route::get('shipping-methods/{shipping_method_id}', [ShippingMethodsController::class, 'show'])->name('shipping-methods.show')->middleware('tenant.has.feature:configure-shipping-method');
            Route::put('shipping-methods/{shipping_method_id}', [ShippingMethodsController::class, 'configureShippingMethod'])->name('shipping-methods.configure')->middleware('tenant.has.feature:configure-shipping-method');
            Route::post('shipping-methods/select-for-local-pickup', [ShippingMethodsController::class, 'selectLocalPickupMethod'])->name('shipping-methods.select-local-pickup')->middleware('tenant.has.feature:select-local-pickup-method');
            Route::post('shipping-methods/select-for-home-delivery', [ShippingMethodsController::class, 'selectHomeDeliveryMethod'])->name('shipping-methods.select-home-delivery')->middleware('tenant.has.feature:select-home-delivery-method');
            Route::post('shipping-methods/enable-local-pickup', [ShippingMethodsController::class, 'enableLocalPickup'])->name('shipping-methods.enable-local-pickup')->middleware('tenant.has.feature:enable-local-pickup');
            Route::post('shipping-methods/enable-home-delivery', [ShippingMethodsController::class, 'enableHomeDelivery'])->name('shipping-methods.enable-home-delivery')->middleware('tenant.has.feature:enable-home-delivery');

            Route::get('zones', [ZoneController::class, 'index'])->name('shipping-zones.index')->middleware('tenant.has.feature:configure-shipping-method');
            Route::put('zones/bulk-activation', [ZoneController::class, 'bulkActivation'])->name('shipping-zones.bulk-activation')->middleware('tenant.has.feature:configure-shipping-method');
        });
    });
});
