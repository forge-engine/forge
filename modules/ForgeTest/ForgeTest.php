<?php

namespace Forge\Modules\ForgeTest;

use Forge\Modules\ForgeTest\Contracts\ForgeTestInterface;
use Forge\Core\Helpers\Debug;

class ForgeTest implements ForgeTestInterface
{
    public function __construct()
    {
        
    }
    public function test(): void
    {
        // Module logic here
        Debug::message("[ForgeTestModule] Called", "start"); // Example log
    }
}