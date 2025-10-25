<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting;

use App\Modules\ForgeTesting\Attributes\AfterEach;
use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Traits\Assertions;
use App\Modules\ForgeTesting\Traits\CacheTesting;
use App\Modules\ForgeTesting\Traits\DatabaseTesting;
use App\Modules\ForgeTesting\Traits\HttpTesting;
use App\Modules\ForgeTesting\Traits\PerformanceTesting;
use Forge\Core\Bootstrap\Bootstrap;
use Forge\Core\Http\Kernel;
use RuntimeException;

abstract class TestCase
{
    use Assertions;

    use DatabaseTesting;
    use PerformanceTesting;
    use HttpTesting;
    use CacheTesting;

    protected static ?Kernel $kernel = null;

    #[BeforeEach]
    public function setup(): void
    {
        if (self::$kernel === null) {
            $bootstrap = Bootstrap::getInstance();
            self::$kernel = $bootstrap->getKernel();
        }
    }

    #[AfterEach]
    public function tearDown(): void
    {
    }

    protected function markTestIncomplete(string $message = ""): void
    {
        throw new RuntimeException("TEST_INCOMPLETE: $message");
    }

    protected function markTestSkipped(string $message = ""): void
    {
        throw new RuntimeException("TEST_SKIPPED: $message");
    }
}
