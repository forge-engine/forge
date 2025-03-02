<?php

namespace Forge\Modules\ForgeOrm\Contracts;

use Forge\Modules\ForgeOrm\Schema\Schema;

interface ForgeOrmInterface
{
    /**
     * Get a schema builder instance.
     *
     * @return Schema
     */
    public function schema(): Schema;
}