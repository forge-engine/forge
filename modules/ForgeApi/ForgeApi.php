<?php

namespace Forge\Modules\ForgeApi;

use Forge\Modules\ForgeApi\Contracts\ForgeApiInterface;
use Forge\Core\Helpers\Debug;

class ForgeApi implements ForgeApiInterface
{
    public function __construct()
    {
        
    }
    public function test(): void
    {
        // Module logic here
        Debug::message("[ForgeApiModule] Called", "start"); // Example log
    }
}