<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->boolean('is_external')->default(false)->after('base_amount');
            $table->text('external_payment_instructions')->nullable()->after('is_external');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['is_external', 'external_payment_instructions']);
        });
    }
};
