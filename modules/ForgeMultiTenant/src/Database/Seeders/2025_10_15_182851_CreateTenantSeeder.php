<?php

declare(strict_types=1);

use Forge\Core\Database\Seeders\Seeder;
use Forge\Core\Database\Seeders\Attributes\SeederInfo;
use Forge\Core\Database\Seeders\Attributes\AutoRollback;

#[SeederInfo(description: 'Seed for TenantSeeder', author: 'Forge Team')]
#[AutoRollback('tenants', ['domain' => 'central'])]
class CreateTenantSeeder extends Seeder
{
    public function up(): void
    {
        $this->insertBatch('tenants', [[
                'id'         => 'central',
                'domain'     => env('CENTRAL_DOMAIN', 'forge-v3.test'),
                'subdomain'  => null,
                'strategy'   => 'column',
                'db_name'    => null,
                'connection' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]]);
        $this->insertBatch('tenants', [[
            'id'         => 'my-tenant',
            'domain'     => 'forge-v3.test',
            'subdomain'  => 'my-tenant',
            'strategy'   => 'column',
            'db_name'    => null,
            'connection' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]]);
    }
}