<?php

namespace Forge\Modules\ForgeOrm\Migrations;

use Forge\Core\Helpers\App;
use Forge\Modules\ForgeOrm\Schema\Schema;

abstract class Migration
{
    protected Schema $schema;

    public function __construct()
    {
        $container = App::getContainer();
        $this->schema = $container->get(Schema::class);
    }

    abstract public function up(): void;

    abstract public function down(): void;
}