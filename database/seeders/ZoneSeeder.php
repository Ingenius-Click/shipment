<?php

namespace Ingenius\Shipment\Database\Seeders;

use Illuminate\Database\Seeder;
use Ingenius\Shipment\Actions\SeedCubanZonesAction;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(SeedCubanZonesAction::class)->handle();
    }
}