<?php
declare(strict_types=1);

namespace Forge\CLI;

use Forge\CLI\Traits\OutputHelper;

abstract class Command
{
    use OutputHelper;

    abstract public function execute(array $args): int;

    protected function argument(string $name, array $args): ?string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, "--$name=")) {
                return substr($arg, strlen("--$name="));
            }
        }

        return null;
    }
}
