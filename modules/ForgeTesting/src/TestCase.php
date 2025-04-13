<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting;

use App\Modules\ForgeTesting\Attributes\AfterEach;
use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Traits\Assertions;
use App\Modules\ForgeTesting\Traits\DatabaseTesting;
use App\Modules\ForgeTesting\Traits\PerformanceTesting;

abstract class TestCase
{
    use Assertions;
    use DatabaseTesting;
    use PerformanceTesting;

    #[BeforeEach]
    public function setup(): void
    {
    }

    #[AfterEach]
    public function tearDown(): void
    {
    }

    protected function markTestIncomplete(string $message = ''): void
    {
        throw new \RuntimeException("TEST_INCOMPLETE: $message");
    }

    protected function markTestSkipped(string $message = ''): void
    {
        throw new \RuntimeException("TEST_SKIPPED: $message");
    }
}
