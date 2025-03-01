<?php

namespace Forge\Console\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class ListModulesCommand implements CommandInterface
{
    use OutputHelper;

    public function __construct()
    {
    }

    public function getName(): string
    {
        return 'list:modules';
    }

    public function getDescription(): string
    {
        return 'List loaded modules';
    }

    public function execute(array $args): int
    {
        return 0;
    }
}
