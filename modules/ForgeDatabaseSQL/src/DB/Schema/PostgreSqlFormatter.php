<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Schema;

use DateTimeInterface;

final class PostgreSqlFormatter implements FormatterInterface
{
    public bool $skipForeignKeys = false;
    private array $relationships = [];

    public function formatColumn(string $name, array $attributes): string
    {
        $typeMapping = [
            'UUID' => 'UUID',
            'STRING' => 'VARCHAR(255)',
            'TEXT' => 'TEXT',
            'INTEGER' => 'INTEGER',
            'BOOLEAN' => 'BOOLEAN',
            'FLOAT' => 'REAL',
            'DECIMAL' => 'DECIMAL',
            'DATE' => 'DATE',
            'DATETIME' => 'TIMESTAMP',
            'TIMESTAMP' => 'TIMESTAMP',
            'ENUM' => $this->formatEnum($attributes),
            'JSON' => 'JSONB',
            'BLOB' => 'BYTEA',
            'ARRAY' => 'TEXT[]',
        ];

        $dbType = $typeMapping[$attributes['type']] ?? $attributes['type'];

        $definition = [
            "\"$name\"",
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

        $values = array_map(fn($v) => "'$v'", $attributes['enum']);
        return 'VARCHAR(255)';
    }

    private function getPrimaryKeyClause(array $attributes): string
    {
        if (!$attributes['primary']) {
            return '';
        }

        return 'PRIMARY KEY';
    }

    private function formatDefault(mixed $value): string
    {
        if ($value === 'CURRENT_TIMESTAMP') {
            return 'DEFAULT CURRENT_TIMESTAMP';
        }

        if (is_bool($value)) {
            return 'DEFAULT ' . ($value ? 'TRUE' : 'FALSE');
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
        $columns = array_map(fn($col) => "\"$col\"", $index['columns']);
        return sprintf(
            'CREATE %sINDEX IF NOT EXISTS "%s" ON "%s" (%s)',
            $index['unique'] ? 'UNIQUE ' : '',
            $index['name'],
            $index['table'],
            implode(', ', $columns)
        );
    }

    public function formatTableOptions(): string
    {
        return '';
    }

    public function addRelationship(string $type, array $config): void
    {
        $this->relationships[] = compact('type', 'config');
    }

    public function formatRelationships(string $table): string
    {
        if ($this->skipForeignKeys ?? false) {
            return '';
        }

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
            'ALTER TABLE "%s" ADD FOREIGN KEY ("%s") REFERENCES "%s"(id) ON DELETE %s',
            $table,
            $config['foreignKey'],
            $config['relatedTable'],
            $config['onDelete']
        );
    }

    private function formatManyToMany(array $config): string
    {
        return sprintf(
            'CREATE TABLE "%s" (
                "%s" INTEGER NOT NULL,
                "%s" INTEGER NOT NULL,
                PRIMARY KEY ("%s", "%s"),
                FOREIGN KEY ("%s") REFERENCES "%s"(id) ON DELETE CASCADE,
                FOREIGN KEY ("%s") REFERENCES "%s"(id) ON DELETE CASCADE
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
