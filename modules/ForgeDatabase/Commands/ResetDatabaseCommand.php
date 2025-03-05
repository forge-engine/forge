<?php

namespace Forge\Modules\ForgeDatabase\Commands;

use Forge\Core\Helpers\App;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Core\Configuration\Config;
use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class ResetDatabaseCommand implements CommandInterface
{
    use OutputHelper;

    /**
     * @inject
     */
    private DatabaseInterface $db;
    /**
     * @inject
     */
    private Config $config;

    public function __construct()
    {
    }

    public function getName(): string
    {
        return 'db:reset';
    }

    public function getDescription(): string
    {
        return 'Reset database, drop database and recreated.';
    }

    /**
     * @param array<int,mixed> $args
     */
    public function execute(array $args): int
    {
        $databaseParams = $this->config->get('forge_database');
        $connection = $databaseParams['connections'][$databaseParams['default']];
        $connectionName = $connection['database'];

        $this->db->beginTransaction();
        try {
            $this->log("Droping [$connectionName] database..");
            $this->db->query("DROP database $connectionName");
            $this->log("Recreating [$connectionName] database..");
            $this->db->query("CREATE database $connectionName");
            $this->success("Database [$connectionName] created succesfully");
            $this->comment('Please run your migrations again.');
        } catch (\Throwable $e) {
            $this->error("Error reseting database.");
            throw $e;
        }

        return 0;
    }
}