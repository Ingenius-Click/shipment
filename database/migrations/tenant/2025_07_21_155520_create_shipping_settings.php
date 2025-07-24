<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ingenius\Core\Facades\Settings;
use Ingenius\Shipment\Enums\ShippingTypes;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Settings::set('shipping', 'local_pickup_enabled', true);
        Settings::set('shipping', 'local_pickup_method', 'local_pickup');

        Settings::set('shipping', 'home_delivery_enabled', false);
        Settings::set('shipping', 'home_delivery_method', '');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Settings::forget('shipping', 'local_pickup_enabled');
        Settings::forget('shipping', 'local_pickup_method');

        Settings::forget('shipping', 'home_delivery_enabled');
        Settings::forget('shipping', 'home_delivery_method');
    }
};
