<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Contracts;

interface Queueinterface
{
    public function push(string $payload): void;
    public function pop(): ?array;
    public function count(): int;
    public function clear(): void;
    public function release(int $jobId, int $delay = 0): void;
}
