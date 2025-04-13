<?php

namespace App\Modules\ForgeTesting\Traits;

trait DatabaseTesting
{
    private static bool $migrated = false;

    public function refreshDatabase(): void
    {
        if (!self::$migrated) {
            $this->runMigrations();
            self::$migrated = true;
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function seed(string $seederClass): void
    {
        (new $seederClass())->run();
    }

    private function runMigrations(): void
    {
        // Run database migrations
    }
}
