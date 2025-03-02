<?php

namespace Forge\Modules\ForgeDatabase\Commands;

use Forge\Core\Helpers\App;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class ResetDatabaseCommand implements CommandInterface
{
    use OutputHelper;

    private DatabaseInterface $db;
    private string $databaseName;

    public function __construct()
    {
        $config = App::config();
        $databaseParams = $config->get('database');
        $connection = $databaseParams['connections'][$databaseParams['default']];
        $databaseName = $connection['database'];
        $databaseInstance = App::getContainer()->get(DatabaseInterface::class);
        $this->db = $databaseInstance;
        $this->databaseName = $databaseName;
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

        $this->db->beginTransaction();
        try {
            $this->log("Droping [$this->databaseName] database..");
            $this->db->query("DROP database $this->databaseName");
            $this->log("Recreating [$this->databaseName] database..");
            $this->db->query("CREATE database $this->databaseName");
            $this->success("Database [$this->databaseName] created succesfully");
            $this->comment('Please run your migrations again.');
        } catch (\Throwable $e) {
            $this->error("Error reseting database.");
            throw $e;
        }

        return 0;
    }
}