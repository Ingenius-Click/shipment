<?php

namespace Ingenius\Shipment\Console\Commands;

use Illuminate\Console\Command;
use Ingenius\Core\Models\Tenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Tenancy;

class SeedZonesCommand extends Command
{
    protected $signature = 'ingenius:shipment:seed-zones {--tenant= : The tenant ID to seed zones for. If not provided, seed for all tenants}';

    protected $description = 'Seed zones (Cuban provinces and municipalities) for tenant(s)';

    public function __construct(
        protected Tenancy $tenancy
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenant = Tenant::findOrFail($tenantId);

            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return 1;
            }

            $this->seedZonesForTenant($tenant);
            $this->info("Zones seeded for tenant {$tenantId}.");
            return 0;
        }

        // Seed for all tenants
        $tenants = Tenant::all();
        $tenantCount = count($tenants);

        if ($tenantCount === 0) {
            $this->info("No tenants found to seed zones.");
            return 0;
        }

        $this->info("Seeding zones for {$tenantCount} tenants...");

        $progressBar = $this->output->createProgressBar($tenantCount);
        $progressBar->start();

        foreach ($tenants as $tenant) {
            if ($tenant instanceof TenantWithDatabase) {
                $this->seedZonesForTenant($tenant);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Zones seeded for all tenants.");

        return 0;
    }

    protected function seedZonesForTenant(TenantWithDatabase $tenant): void
    {
        $this->tenancy->initialize($tenant);

        // Include the seeder file directly
        $seederPath = __DIR__ . '/../../../database/seeders/ZoneSeeder.php';
        require_once $seederPath;

        $seederClass = 'Ingenius\Shipment\Database\Seeders\ZoneSeeder';
        $seeder = new $seederClass();
        $seeder->run();

        $this->tenancy->end();
    }
}