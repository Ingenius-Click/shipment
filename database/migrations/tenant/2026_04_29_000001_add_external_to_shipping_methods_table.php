<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('shipment.shipping_methods_table', 'shipping_methods');

        Schema::table($table, function (Blueprint $table) {
            $table->boolean('is_external')->default(false)->after('active');
            $table->text('external_payment_instructions')->nullable()->after('is_external');
        });
    }

    public function down(): void
    {
        $table = config('shipment.shipping_methods_table', 'shipping_methods');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn(['is_external', 'external_payment_instructions']);
        });
    }
};
