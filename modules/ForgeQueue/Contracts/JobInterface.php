<?php

namespace Forge\Modules\ForgeQueue\Contracts;

interface JobInterface
{

    public function __construct(...$args);

    public function serialize(): array;

    public function handle(): void;

    public function failed(\Throwable $exception): void;
}
