<?php

namespace Forge\Modules\ForgeOrm\Schema;

use Forge\Core\Helpers\App;

class Blueprint
{
    /**
     * @var string[] Array to store column definitions.
     */
    protected array $columns = [];
    /**
     * @var array[] Array to store relationship definitions.
     */
    protected array $relationships = [];

    private string $dbDriver;

    public function __construct()
    {
        $this->dbDriver = App::env('FORGE_DB_CONNECTION');
    }

    /**
     * Add a string column to the blueprint.
     *
     * @param string $column The name of the column.
     * @param int $length The maximum length of the string (default: 255).
     * @return $this
     */
    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = "{$column} VARCHAR({$length})";
        return $this;
    }

    /**
     * Add a text column (for longer strings) to the blueprint.
     *
     * @param string $column The name of the column.
     * @return $this
     */
    public function text(string $column): self
    {
        $this->columns[] = "{$column} TEXT";
        return $this;
    }

    /**
     * Add a long text column (for very long strings) to the blueprint.
     *
     * @param string $column The name of the column.
     * @return $this
     */
    public function longText(string $column): self
    {
        $this->columns[] = "{$column} LONGTEXT";
        return $this;
    }

    /**
     * Add an integer column to the blueprint.
     *
     * @param string $column The name of the column.
     * @param bool $autoIncrement Set to true for auto-incrementing (default: false).
     * @param bool $unsigned Set to true for unsigned integer (default: false).
     * @return $this
     */
    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): self
    {
        $type = 'INTEGER';
        if ($unsigned) {
            $type = 'INTEGER UNSIGNED';
        }
        if ($autoIncrement) {
            if ($this->dbDriver === 'mysql') {
                $type .= ' AUTO_INCREMENT';
            } elseif ($this->dbDriver === 'pgsql') {
                $type = 'SERIAL';
            } elseif ($this->dbDriver === 'sqlite') {
                $type = 'INTEGER AUTOINCREMENT';
            } else {
                $type .= ' AUTO_INCREMENT';
            }
        }
        $this->columns[] = "{$column} {$type}";
        return $this;
    }

    /**
     * Add a tiny integer column to the blueprint.
     *
     * @param string $column The name of the column.
     * @param bool $autoIncrement Set to true for auto-incrementing (default: false).
     * @param bool $unsigned Set to true for unsigned integer (default: false).
     * @return $this
     */
    public function tinyInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): self
    {
        $type = 'TINYINT';

        if ($unsigned) {
            $type = 'TINYINT UNSIGNED';
        }

        if ($autoIncrement) {
            if ($this->dbDriver === 'mysql') {
                $type .= ' AUTO_INCREMENT';
            } elseif ($this->dbDriver === 'pgsql') {
                $type = 'SMALLSERIAL';
            } elseif ($this->dbDriver === 'sqlite') {
                $type = 'INTEGER AUTOINCREMENT';
            } else {
                $type .= ' AUTO_INCREMENT';
            }
        }

        $this->columns[] = "{$column} {$type}";

        return $this;
    }

    /**
     * Add a big integer column to the blueprint.
     *
     * @param string $column The name of the column.
     * @param bool $autoIncrement Set to true for auto-incrementing (default: false).
     * @param bool $unsigned Set to true for unsigned big integer (default: false).
     * @return $this
     */
    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): self
    {
        $type = 'BIGINT';
        if ($unsigned) {
            $type = 'BIGINT UNSIGNED';
        }
        if ($autoIncrement) {
            if ($this->dbDriver === 'mysql') {
                $type .= ' AUTO_INCREMENT';
            } elseif ($this->dbDriver === 'pgsql') {
                $type = 'BIGSERIAL';
            } elseif ($this->dbDriver === 'sqlite') {
                $type = 'INTEGER AUTOINCREMENT';
            } else {
                $type .= ' AUTO_INCREMENT';
            }
        }
        $this->columns[] = "{$column} {$type}";
        return $this;
    }

    /**
     * Add an unsigned big integer column to the blueprint.
     *
     * @param string $column The name of the column.
     * @param bool $autoIncrement Set to true for auto-incrementing (default: false).
     * @return $this
     */
    public function unsignedBigInteger(string $column, bool $autoIncrement = false): self
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Add a boolean column to the blueprint.
     *
     * @param string $column The name of the column.
     * @return $this
     */
    public function boolean(string $column): self
    {
        $this->columns[] = "{$column} BOOLEAN";
        return $this;
    }

    /**
     * Add a date column to the blueprint.
     *
     * @param string $column The name of the column.
     * @return $this
     */
    public function date(string $column): self
    {
        $this->columns[] = "{$column} DATE";
        return $this;
    }

    /**
     * Add a datetime column to the blueprint.
     *
     * @param string $column The name of the column.
     * @param int $precision (Optional) Precision for fractional seconds (varies by database).
     * @return $this
     */
    public function datetime(string $column, int $precision = 0): self
    {
        $type = 'DATETIME';
        if ($this->dbDriver === 'pgsql') {
            $type = 'TIMESTAMP';
            if ($precision > 0) {
                $type = "TIMESTAMP({$precision})";
            }
        } else { // MySQL, SQLite
            if ($precision > 0) {
                $type = "DATETIME({$precision})";
            }
        }
        $this->columns[] = "{$column} {$type}";
        return $this;
    }

    /**
     * Add a timestamp column to the blueprint.
     *
     * @param string $column The name of the column.
     * @param int $precision (Optional) Precision for fractional seconds (varies by database).
     * @return $this
     */
    public function timestamp(string $column, int $precision = 0): self
    {
        $type = 'TIMESTAMP';
        if ($this->dbDriver === 'sqlite') {
            $type = 'DATETIME';
            if ($precision > 0) {
                $type = "DATETIME({$precision})";
            }
        } else {
            if ($precision > 0) {
                $type = "TIMESTAMP({$precision})";
            }
        }
        $this->columns[] = "{$column} {$type}";
        return $this;
    }

    /**
     * Add timestamps (created_at and updated_at columns).
     *
     * @param int $precision (Optional) Precision for fractional seconds in timestamps.
     * @return $this
     */
    public function timestamps(int $precision = 0): self
    {
        $this->datetime('created_at', $precision);
        $this->datetime('updated_at', $precision);
        return $this;
    }

    /**
     * Add a JSON column to the blueprint.
     *
     * @param string $column The name of the column.
     * @return $this
     */
    public function json(string $column): self
    {
        $this->columns[] = "{$column} JSON"; // JSON support varies by database type/version
        return $this;
    }

    /**
     * Add a UUID column to the blueprint.
     *
     * @param string $column The name of the column.
     * @return $this
     */
    public function uuid(string $column): self
    {
        $this->columns[] = "{$column} CHAR(36)";
        return $this;
    }

    /**
     * Add a primary key constraint to the blueprint (usually for 'id' column).
     *
     * @param string|array $columns The column(s) to set as the primary key.
     * @return $this
     */
    public function primaryKey(string|array $columns): self
    {
        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
        $this->columns[] = "PRIMARY KEY ({$columnList})";
        return $this;
    }

    /**
     * Add a primary key constraint to the blueprint for a specific column.
     *
     * @param string|null $column The name of the column to set as the primary key. If null, use the last defined column.
     * @return $this
     */
    public function primary(string $column = null): self
    {
        if ($column === null && !empty($this->columns)) {
            $lastColumn = array_pop($this->columns);
            preg_match('/^\w+/', $lastColumn, $matches);
            $column = $matches[0];
            $this->columns[] = $lastColumn;
        }


        $this->columns[] = "PRIMARY KEY ({$column})";
        return $this;
    }

    /**
     * Add a unique index to a column(s).
     *
     * @param string|array $columns The column(s) to make unique.
     * @param string|null $indexName Optional name for the index.
     * @return $this
     */
    public function unique(string|array $columns, ?string $indexName = null): self
    {
        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
        $indexClause = $indexName ? "UNIQUE INDEX {$indexName} " : 'UNIQUE ';
        $this->columns[] = "{$indexClause}({$columnList})";
        return $this;
    }


    /**
     * Add a generic index to a column(s).
     *
     * @param string|array $columns The column(s) to index.
     * @param string|null $indexName Optional name for the index.
     * @return $this
     */
    public function index(string|array $columns, ?string $indexName = null): self
    {
        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
        $indexClause = $indexName ? "INDEX {$indexName} " : 'INDEX ';
        $this->columns[] = "{$indexClause}({$columnList})";
        return $this;
    }

    /**
     * Allow the column to accept NULL values.
     * This is a modifier and should be chained after defining a column type.
     *
     * @return $this
     */
    public function nullable(): self
    {
        if (!empty($this->columns)) {
            $lastColumn = array_pop($this->columns);
            $this->columns[] = "{$lastColumn} NULL";
        }
        return $this;
    }

    /**
     * Set a default value for the column.
     * This is a modifier and should be chained after defining a column type.
     *
     * @param mixed $value The default value.
     * @return $this
     */
    public function default(mixed $value): self
    {
        if (!empty($this->columns)) {
            $lastColumn = array_pop($this->columns);
            $defaultValue = match (true) {
                is_string($value) => "'" . addslashes($value) . "'", // Escape strings
                is_bool($value) => $value ? 'TRUE' : 'FALSE',      // Boolean to TRUE/FALSE
                is_null($value) => 'NULL',
                default => (string)$value,                        // Numbers, etc., as string
            };

            $this->columns[] = "{$lastColumn} DEFAULT {$defaultValue}";
        }
        return $this;
    }

    /**
     * Add a comment to the column definition.
     * This is a modifier and should be chained after defining a column type.
     *
     * @param string $comment The comment text.
     * @return $this
     */
    public function comment(string $comment): self
    {
        if (!empty($this->columns)) {
            $lastColumn = array_pop($this->columns);
            $this->columns[] = "{$lastColumn} COMMENT '" . addslashes($comment) . "'"; // Escape comment
        }
        return $this;
    }

    /**
     * Define a "belongs to" relationship (e.g., a comment belongs to a post).
     * This adds a column as a "reference" to another table.
     *
     * @param string $relatedTable The name of the table it belongs to.
     * @param string $relatedColumn The column in the related table (defaults to 'id').
     * @param string|null $column The column to be added in the current table (defaults to 'related_table_id', e.g., 'post_id').
     * @param string|null $constraintName Optional name for the relationship constraint.
     * @param string|null $onDelete Optional action for ON DELETE (e.g., 'CASCADE', 'SET NULL').
     * @return $this
     */
    public function belongsTo(string $relatedTable, string $relatedColumn = 'id', string $column = null, ?string $constraintName = null, ?string $onDelete = null): self
    {
        $columnName = $column ?? strtolower($relatedTable) . '_' . $relatedColumn;

        $this->relationships[] = [
            'type' => 'belongsTo',
            'relatedTable' => $relatedTable,
            'relatedColumn' => $relatedColumn,
            'column' => $columnName,
            'constraintName' => $constraintName,
            'onDelete' => $onDelete,
        ];
        return $this;
    }

    /**
     * Define a "has many" relationship (e.g., an author has many books).
     * This is primarily for documenting the relationship in the blueprint,
     * as the actual column (reference) is usually defined in the 'belongsTo' side.
     *
     * @param string $relatedTable The name of the related table that "belongs to" this table.
     * @param string|null $relatedColumn The column in the related table that establishes the relationship (defaults to 'related_table_id').
     * @param string|null $column The column in the current table (often 'id', defaults to 'id').
     * @param string|null $constraintName Optional name for the relationship constraint (if applicable).
     * @param string|null $onDelete Optional action for ON DELETE (e.g., 'CASCADE', 'SET NULL').
     * @return $this
     */
    public function hasMany(string $relatedTable, string $relatedColumn = null, string $column = 'id', ?string $constraintName = null, ?string $onDelete = null): self
    {
        $relatedReferenceColumn = $relatedColumn ?? strtolower(basename(str_replace('\\', '/', __CLASS__))) . '_' . $column;
        $this->relationships[] = [
            'type' => 'hasMany',
            'relatedTable' => $relatedTable,
            'relatedColumn' => $relatedReferenceColumn,
            'column' => $column,
            'constraintName' => $constraintName,
            'onDelete' => $onDelete,
        ];
        return $this;
    }

    /**
     * Define a "has one" relationship (e.g., a user has one profile).
     * This is similar to 'hasMany' but indicates a one-to-one relationship.
     *
     * @param string $relatedTable The name of the related table that "belongs to" this table.
     * @param string|null $relatedColumn The column in the related table that establishes the relationship (defaults to 'related_table_id').
     * @param string|null $column The column in the current table (often 'id', defaults to 'id').
     * @param string|null $constraintName Optional name for the relationship constraint (if applicable).
     * @param string|null $onDelete Optional action for ON DELETE (e.g., 'CASCADE', 'SET NULL').
     * @return $this
     */
    public function hasOne(string $relatedTable, string $relatedColumn = null, string $column = 'id', ?string $constraintName = null, ?string $onDelete = null): self
    {
        $relatedReferenceColumn = $relatedColumn ?? strtolower(basename(str_replace('\\', '/', __CLASS__))) . '_' . $column;
        $this->relationships[] = [
            'type' => 'hasOne',
            'relatedTable' => $relatedTable,
            'relatedColumn' => $relatedReferenceColumn,
            'column' => $column,
            'constraintName' => $constraintName,
            'onDelete' => $onDelete,
        ];
        return $this;
    }

    /**
     * Define a many-to-many relationship (e.g., a post belongs to many tags).
     * This method sets up the pivot table and foreign key constraints.
     *
     * @param string $relatedTable The name of the related table.
     * @param string $pivotTable The name of the pivot table (defaults to a combination of the current and related table names).
     * @param string|null $foreignPivotKey The foreign key column in the pivot table for the current table (defaults to 'current_table_id').
     * @param string|null $relatedPivotKey The foreign key column in the pivot table for the related table (defaults to 'related_table_id').
     * @param string|null $onDelete Optional action for ON DELETE (e.g., 'CASCADE', 'SET NULL').
     * @return $this
     */
    public function belongsToMany(string $relatedTable, string $pivotTable = null, string $foreignPivotKey = null, string $relatedPivotKey = null, ?string $onDelete = null): self
    {
        $currentTable = strtolower(basename(str_replace('\\', '/', __CLASS__)));
        $pivotTable = $pivotTable ?? ($currentTable . '_' . $relatedTable);
        $foreignPivotKey = $foreignPivotKey ?? ($currentTable . '_id');
        $relatedPivotKey = $relatedPivotKey ?? ($relatedTable . '_id');

        $this->relationships[] = [
            'type' => 'belongsToMany',
            'relatedTable' => $relatedTable,
            'pivotTable' => $pivotTable,
            'foreignPivotKey' => $foreignPivotKey,
            'relatedPivotKey' => $relatedPivotKey,
            'onDelete' => $onDelete,
        ];

        return $this;
    }

    /**
     * Get the array of foreign key definitions.
     *
     * @return array<array>
     */
    public function getForeignKeys(): array
    {
        $foreignKeys = [];
        foreach ($this->relationships as $relationship) {
            if ($relationship['type'] === 'belongsToMany') {
                $foreignKeys[] = "FOREIGN KEY ({$relationship['foreignPivotKey']}) REFERENCES {$this->currentTable}({$this->primaryKey})" .
                    ($relationship['onDelete'] ? " ON DELETE {$relationship['onDelete']}" : '');
                $foreignKeys[] = "FOREIGN KEY ({$relationship['relatedPivotKey']}) REFERENCES {$relationship['relatedTable']}({$this->primaryKey})" .
                    ($relationship['onDelete'] ? " ON DELETE {$relationship['onDelete']}" : '');
            } elseif (in_array($relationship['type'], ['belongsTo', 'hasMany', 'hasOne'])) {
                $foreignKey = "FOREIGN KEY ({$relationship['column']}) REFERENCES {$relationship['relatedTable']}({$relationship['relatedColumn']})";
                if ($relationship['onDelete']) {
                    $foreignKey .= " ON DELETE {$relationship['onDelete']}";
                }
                $foreignKeys[] = $foreignKey;
            }
        }
        return $foreignKeys;
    }

    /**
     * Get the array of column definitions.
     *
     * @return array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the array of relationship definitions.
     *
     * @return array<array>
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Reset the blueprint to start defining a new table schema.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->columns = [];
    }
}