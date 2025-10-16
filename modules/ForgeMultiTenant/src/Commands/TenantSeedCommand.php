<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Commands;

use App\Modules\ForgeMultiTenant\Services\TenantManager;
use App\Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Database\Seeders\SeederManager;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(
    name: 'tenant:seed',
    description: 'Run seeders for one or all tenants (app/Database/Seeders/Tenants)'
)]
final class TenantSeedCommand extends Command
{
    use OutputHelper;

    public function __construct(
        private readonly TenantManager           $tenants,
        private readonly TenantConnectionFactory $factory,
        private readonly SeederManager $seeder
    ) {}

    /**
     * @throws \Throwable
     */
    public function execute(array $args): int
    {
        $id      = $this->getArg($args, '--tenant') ?? 'all';
        $preview = in_array('--preview', $args);

        foreach ($this->resolveTenants($id) as $tenant) {
            $this->info("Seeding tenant: {$tenant->id}");
            $conn = $this->factory->forTenant($tenant);
            $this->seeder->setConnection($conn);
            $this->seeder->createSeedsTable();

            if ($preview) {
                $this->dryRun($tenant);
                continue;
            }

            $this->seeder->run('tenants');
        }
        return 0;
    }

    private function dryRun(object $tenant): void
    {
        $pending = $this->seeder->getPendingSeeders('tenants', null);
        if (!$pending) {
            $this->comment("  ✔ no pending seeders");
            return;
        }
        foreach ($pending as $file) {
            $this->line("  - " . basename($file));
        }
    }

    private function resolveTenants(?string $id): array
    {
        return $id === 'all' ? $this->tenants->all() : [$this->tenants->find($id)];
    }

    private function getArg(array $args, string $key): ?string
    {
        foreach ($args as $a) {
            if (str_starts_with($a, $key . '=')) {
                return explode('=', $a, 2)[1] ?? null;
            }
        }
        return null;
    }
}