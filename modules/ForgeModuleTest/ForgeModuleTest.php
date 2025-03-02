<?php

namespace Forge\Modules\ForgeModuleTest;

use Forge\Modules\ForgeModuleTest\Contracts\ForgeModuleTestInterface;
use Forge\Core\Helpers\Debug;

class ForgeModuleTest implements ForgeModuleTestInterface
{
    public function __construct()
    {
        
    }
    public function test(): void
    {
        // Module logic here
        Debug::message("[ForgeModuleTestModule] Called", "start"); // Example log
    }
}