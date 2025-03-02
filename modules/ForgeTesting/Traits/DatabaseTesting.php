<?php

namespace Forge\Modules\ForgeTesting\Traits;

trait DatabaseTesting
{
    private static bool $migrated = false;

    public function refreshDatabase(): void
    {
        if (!self::$migrated) {
            $this->runMigrations();
            self::$migrated = true;
        }


        //$this->container->get(Database::class)->beginTransaction();
    }

    public function tearDown(): void
    {
        //$this->container->get(Database::class)->rollBack();
        parent::tearDown();
    }

    protected function seed(string $seederClass): void
    {
        (new $seederClass)->run();
    }

    private function runMigrations(): void
    {
        // Run database migrations
    }
}