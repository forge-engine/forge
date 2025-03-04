<?php

namespace Forge\Modules\ForgeExplicitOrm;

use Forge\Modules\ForgeExplicitOrm\Contracts\ForgeExplicitOrmInterface;
use Forge\Core\Helpers\Debug;

class ForgeExplicitOrm implements ForgeExplicitOrmInterface
{
    public function __construct()
    {
        
    }
    public function test(): void
    {
        // Module logic here
        Debug::message("[ForgeExplicitOrmModule] Called", "start"); // Example log
    }
}