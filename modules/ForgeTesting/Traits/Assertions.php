<?php

namespace Forge\Modules\ForgeTesting\Traits;

use Forge\Core\Helpers\App;
use Forge\Http\Response;

trait Assertions
{
    protected function assertTrue($actual, string $message = ''): void
    {
        if ($actual !== true) {
            throw new \RuntimeException($message ?: "Expected true, got " . var_export($actual, true));
        }
    }

    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected != $actual) {
            throw new \RuntimeException($message ?: sprintf(
                "Expected %s, got %s",
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }

    protected function assertInstanceOf(string $expected, $actual, string $message = ''): void
    {
        if (!($actual instanceof $expected)) {
            throw new \RuntimeException($message ?: sprintf(
                "Expected instance of %s, got %s",
                $expected,
                is_object($actual) ? get_class($actual) : gettype($actual)
            ));
        }
    }

    protected function assertCount(int $expected, iterable $actual, string $message = ''): void
    {
        $count = is_array($actual) ? count($actual) : iterator_count($actual);
        if ($count !== $expected) {
            throw new \RuntimeException($message ?: "Expected $expected items, got $count");
        }
    }

    protected function assertContains($needle, iterable $haystack, string $message = ''): void
    {
        foreach ($haystack as $item) {
            if ($item === $needle) return;
        }
        throw new \RuntimeException($message ?: "Value not found in collection");
    }

    protected function assertJsonEquals(string $expectedJson, string $actualJson, string $message = ''): void
    {
        $this->assertEquals(
            json_decode($expectedJson, true, 512, JSON_THROW_ON_ERROR),
            json_decode($actualJson, true, 512, JSON_THROW_ON_ERROR),
            $message ?: 'JSON structures do not match'
        );
    }

    protected function assertHttpStatus(int $expected, Response $response, string $message = ''): void
    {
        $this->assertEquals(
            $expected,
            $response->getStatusCode(),
            $message ?: "HTTP status mismatch"
        );
    }

    protected function assertDatabaseHas(string $table, string $column, string $value): void
    {
        $db = App::db();
        $exists = $db
            ->table($table)
            ->where($column, '=', $value)
            ->count();

        $this->assertTrue($exists, "Record not found in $table");
    }
}