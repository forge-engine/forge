<?php

declare(strict_types=1);

namespace Forge\Core\Database\Migrations;

class MigrationException extends \RuntimeException
{
    public function __construct(
        string $message,
        private string $failedSql
    ) {
        parent::__construct($message);
    }

    public function getFailedSql(): string
    {
        return $this->failedSql;
    }
}
