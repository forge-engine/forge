<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Schema;

interface FormatterInterface
{
    public function formatColumn(string $name, array $attributes): string;

    public function formatIndex(array $index): string;

    public function formatTableOptions(): string;

    public function addRelationship(string $type, array $config): void;

    public function formatRelationships(string $table): string;

    public function resetRelationships(): void;
}
