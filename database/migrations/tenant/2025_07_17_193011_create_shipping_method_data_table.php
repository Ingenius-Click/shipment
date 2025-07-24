<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = config('shipment.shipping_methods_table', 'shipping_methods');

        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('shipping_method_id');
            $table->json('calculation_data');
            $table->string('name');
            $table->boolean('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_method_data');
    }
};
