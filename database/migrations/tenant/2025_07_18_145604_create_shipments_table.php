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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->morphs('shippable');
            $table->string('tracking_number')->nullable();
            $table->string('shipping_method_id');
            $table->string('beneficiary_name');
            $table->string('beneficiary_address')->nullable();
            $table->string('beneficiary_email');
            $table->string('beneficiary_city')->nullable();
            $table->string('beneficiary_state')->nullable();
            $table->string('beneficiary_zip')->nullable();
            $table->string('beneficiary_country')->nullable();
            $table->string('beneficiary_phone');
            $table->string('pickup_address')->nullable();
            $table->string('base_currency_code');
            $table->string('currency_code');
            $table->float('exchange_rate');
            $table->integer('base_amount');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
