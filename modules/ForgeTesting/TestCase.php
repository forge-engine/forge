<?php

namespace Forge\Modules\ForgeTesting;

use Forge\Modules\ForgeTesting\Traits\Assertions;
use Forge\Modules\ForgeTesting\Traits\DatabaseTesting;
use Forge\Modules\ForgeTesting\Traits\PerformanceTesting;

abstract class TestCase
{
    use Assertions, PerformanceTesting, DatabaseTesting;

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    protected function markTestIncomplete(string $message = ''): void
    {
        throw new \RuntimeException("Test incomplete: $message");
    }
}