<?php

namespace Forge\Modules\ForgeOrm\Seeder;

use Forge\Modules\Database\Contracts\DatabaseInterface;
use Forge\Modules\ForgeOrm\Schema\Schema;

abstract class Seeder
{
    protected DatabaseInterface $db;
    protected Schema $schema;

    public function __construct()
    {
        $container = App::getContainer();
        $this->db = $container->get(DatabaseInterface::class);
        $this->schema = $container->get(Schema::class);
    }

    abstract public function run(): void;
}