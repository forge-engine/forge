<?php

namespace Forge\Modules\ForgeOrm;

use Forge\Modules\ForgeOrm\Contracts\ForgeOrmInterface;
use Forge\Modules\ForgeOrm\Schema\Schema;

class ForgeOrm implements ForgeOrmInterface
{

    /**
     * Get a schema builder instance.
     *
     * @return Schema
     */
    public function schema(): Schema
    {
        return new Schema();
    }
}