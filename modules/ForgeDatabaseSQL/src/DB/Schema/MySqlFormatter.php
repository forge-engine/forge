<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Schema;

use DateTimeInterface;

final class MySqlFormatter implements FormatterInterface
{
    public bool $skipForeignKeys = false;
    private array $relationships = [];

    public function formatColumn(string $name, array $attributes): string
    {
        $typeMapping = [
            'UUID' => 'CHAR(36)',
            'STRING' => 'VARCHAR(255)',
            'TEXT' => 'TEXT',
            'INTEGER' => 'INT',
            'BOOLEAN' => 'BOOLEAN',
            'FLOAT' => 'FLOAT',
            'DECIMAL' => 'DECIMAL',
            'DATE' => 'DATE',
            'DATETIME' => 'DATETIME',
            'TIMESTAMP' => 'TIMESTAMP',
            'ENUM' => $this->formatEnum($attributes),
            'JSON' => 'JSON',
            'BLOB' => 'BLOB',
            'ARRAY' => 'JSON',
        ];

        $dbType = $typeMapping[$attributes['type']] ?? $attributes['type'];

        $definition = [
            "`$name`",
            $dbType,
            $attributes['nullable'] ? 'NULL' : 'NOT NULL',
            $this->getPrimaryKeyClause($attributes),
            $attributes['unique'] ? 'UNIQUE' : '',
            isset($attributes['default']) ?
                $this->formatDefault($attributes['default']) : ''
        ];

        return implode(' ', array_filter($definition));
    }

    private function formatEnum(array $attributes): string
    {
        if ($attributes['type'] !== 'ENUM' || empty($attributes['enum'])) {
            return 'VARCHAR(255)';
        }

        $values = $attributes['enum'];
        return 'ENUM(' . implode(',', $values) . ')';
    }

    private function getPrimaryKeyClause(array $attributes): string
    {
        if (!$attributes['primary']) {
            return '';
        }

        $clause = 'PRIMARY KEY';

        if ($attributes['autoIncrement'] && $attributes['type'] === 'INT') {
            $clause .= ' AUTO_INCREMENT';
        }

        return $clause;
    }

    private function formatDefault(mixed $value): string
    {
        if ($value === 'CURRENT_TIMESTAMP') {
            return 'DEFAULT CURRENT_TIMESTAMP';
        }

        if (is_bool($value)) {
            return 'DEFAULT ' . ($value ? 1 : 0);
        }

        if (is_string($value)) {
            return "DEFAULT '$value'";
        }

        if ($value instanceof DateTimeInterface) {
            return "DEFAULT '" . $value->format('Y-m-d H:i:s') . "'";
        }

        return "DEFAULT $value";
    }

    public function resetRelationships(): void
    {
        $this->relationships = [];
    }

    public function formatIndex(array $index): string
    {
        $columns = array_map(fn($col) => "`$col`", $index['columns']);
        return sprintf(
            'CREATE %sINDEX IF NOT EXISTS `%s` ON `%s` (%s) USING BTREE',
            $index['unique'] ? 'UNIQUE ' : '',
            $index['name'],
            $index['table'],
            implode(', ', $columns)
        );
    }

    public function formatTableOptions(): string
    {
        return 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    public function addRelationship(string $type, array $config): void
    {
        $this->relationships[] = compact('type', 'config');
    }

    public function formatRelationships(string $table): string
    {
        return implode(";\n", array_map(
            fn($rel) => match ($rel['type']) {
                'belongsTo' => $this->formatBelongsTo($table, $rel['config']),
                'manyToMany' => $this->formatManyToMany($rel['config']),
                default => ''
            },
            $this->relationships
        ));
    }

    private function formatBelongsTo(string $table, array $config): string
    {
        return sprintf(
            'ALTER TABLE `%s` ADD FOREIGN KEY (`%s`) REFERENCES `%s`(id) ON DELETE %s',
            $table,
            $config['foreignKey'],
            $config['relatedTable'],
            $config['onDelete']
        );
    }

    private function formatManyToMany(array $config): string
    {
        return sprintf(
            'CREATE TABLE `%s` (
                `%s` INT UNSIGNED NOT NULL,
                `%s` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`%s`, `%s`),
                FOREIGN KEY (`%s`) REFERENCES `%s`(id) ON DELETE CASCADE,
                FOREIGN KEY (`%s`) REFERENCES `%s`(id) ON DELETE CASCADE
            )',
            $config['joinTable'],
            $config['foreignKey'],
            $config['relatedKey'],
            $config['foreignKey'],
            $config['relatedKey'],
            $config['foreignKey'],
            $config['sourceTable'],
            $config['relatedKey'],
            $config['relatedTable']
        );
    }
}
