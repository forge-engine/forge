<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Commands;

use App\Modules\ForgeMultiTenant\Services\TenantManager;
use App\Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Database\Migrator;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(
    name: 'tenant:migrate',
    description: 'Run migrations for one or all tenants (app/Database/Migrations/Tenants)'
)]
final class TenantMigrateCommand extends Command
{
    use OutputHelper;

    public function __construct(
        private readonly TenantManager           $tenants,
        private readonly TenantConnectionFactory $factory,
        private readonly Migrator $migrator
    ) {}

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function execute(array $args): int
    {
        $id     = $this->getArg($args, '--tenant') ?? 'all';
        $preview = in_array('--preview', $args);

        foreach ($this->resolveTenants($id) as $tenant) {
            $this->info("Migrating tenant: {$tenant->id}");
            $this->migrator->setConnection($this->factory->forTenant($tenant));
            $this->migrator->createMigrationTable();
            $preview
                ? $this->dryRun($tenant)
                : $this->migrator->run('all', null, 'tenants');
        }
        return 0;
    }

    /**
     * @throws \ReflectionException
     */
    private function dryRun(object $tenant): void
    {
        $pending = $this->migrator->previewRun('app', null, 'tenants');
        if (!$pending) {
            $this->comment("  ✔ no pending migrations");
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