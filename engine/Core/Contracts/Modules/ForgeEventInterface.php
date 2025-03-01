<?php

namespace Forge\Core\Contracts\Modules;

interface ForgeEventInterface
{
    public function getName(): string;

    public function getPayload(): mixed;
}