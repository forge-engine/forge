<?php

namespace Forge\Core\Contracts\Command;

interface CommandInterface
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * @param array<int,mixed> $args
     */
    public function execute(array $args): int;
}
