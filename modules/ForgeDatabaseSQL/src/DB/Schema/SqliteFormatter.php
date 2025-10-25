<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Schema;

use DateTimeInterface;

class SqliteFormatter implements FormatterInterface
{
    public bool $skipForeignKeys = true;
    private array $relationships = [];

    public function formatColumn(string $name, array $attributes): string
    {
        $typeMapping = [
            'UUID' => 'TEXT',
            'STRING' => 'TEXT',
            'TEXT' => 'TEXT',
            'INTEGER' => 'INTEGER',
            'BOOLEAN' => 'INTEGER',
            'FLOAT' => 'REAL',
            'DECIMAL' => 'REAL',
            'DATE' => 'TEXT',
            'DATETIME' => 'TEXT',
            'TIMESTAMP' => 'DATETIME',
            'ENUM' => 'TEXT',
            'JSON' => 'JSON',
            'BLOB' => 'BLOB',
            'ARRAY' => 'TEXT',
        ];

        $dbType = $typeMapping[$attributes['type']] ?? $attributes['type'];

        $definition = [
            "\"$name\"",
            $dbType,
            $attributes['nullable'] ? '' : 'NOT NULL',
            $this->getPrimaryKeyClause($attributes),
            $attributes['unique'] ? 'UNIQUE' : '',
            isset($attributes['default']) ?
                $this->formatDefault($attributes['default']) : ''
        ];

        return implode(' ', array_filter($definition));
    }

    private function getPrimaryKeyClause(array $attributes): string
    {
        if (!$attributes['primary']) {
            return '';
        }

        $clause = 'PRIMARY KEY';

        if ($attributes['autoIncrement'] && $attributes['type'] === 'INT') {
            $clause .= ' AUTOINCREMENT';
        }

        return $clause;
    }

    private function formatDefault(mixed $value): string
    {
        if ($value === 'CURRENT_TIMESTAMP') {
            return 'DEFAULT CURRENT_TIMESTAMP';
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
            'CREATE %sINDEX "%s" ON "%s" (%s)',
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

    private function formatEnum(array $attributes): string
    {
        if ($attributes['type'] !== 'ENUM' || empty($attributes['enum'])) {
            return 'TEXT';
        }

        $values = array_map(fn($v) => "'$v'", $attributes['enum']);
        return 'TEXT CHECK ("' . implode('" OR "', $values) . '")';
    }
}
