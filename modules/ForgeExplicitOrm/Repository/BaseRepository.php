<?php

namespace Forge\Modules\ForgeExplicitOrm\Repository;

use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeExplicitOrm\Contracts\RepositoryInterface;
use Forge\Modules\ForgeExplicitOrm\Exception\RepositoryException;

abstract class BaseRepository implements RepositoryInterface
{
    public DatabaseInterface $database;

    /**
     * @var class-string The fully qualified class name of the DTO for this repository.
     * @phpstan-var class-string
     */
    protected string $dtoClass;

    /**
     * @var string The name of the database table. Concrete repositories must define this.
     */
    protected string $table;

    /**
     * BaseRepository constructor
     *
     * @param DatabaseInterface $database
     * @throws RepositoryException if the DTO class or table is not defined.
     */
    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;

        if (empty($this->dtoClass)) {
            throw new RepositoryException(sprintf('DTO class not defined in %s.', static::class));
        }

        if (empty($this->table)) {
            throw new RepositoryException(sprintf('Table name not defined in %s.', static::class));
        }
    }

    /**
     * Find a record by ID.
     *
     * @param int|string $id
     * @return ?object DTO object or null if not found
     * @throws RepositoryException IF there's a database error.
     */
    public function find(int|string $id): ?object
    {
        try {
            $sql = sprintf('SELECT * FROM %s WHERE id = :id', $this->table);
            $params = [':id' => $id];
            $data = $this->database->query($sql, $params);

            if (!$data) {
                return null;
            }

            $firstRow = isset($data[0]) ? $data[0] : null;
            if (!$firstRow) {
                return null;
            }

            return $this->createDtoFromData($firstRow);
        } catch (\Throwable $e) {
            throw new RepositoryException(
                sprintf('Error finding record with ID %s in table %s: %s', $id, $this->table, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Find all records.
     *
     * @return array<Object> Array of DTO objects
     * @throws RepositoryException If there's a database error.
     */
    public function findAll(): array
    {
        try {
            $sql = sprintf('SELECT * FROM %s', $this->table);
            $results = $this->database->query($sql);

            $dtos = [];
            foreach ($results as $data) {
                $dtos[] = $this->createDtoFromData($data);
            }

            return $dtos;
        } catch (\Throwable $e) {
            throw new RepositoryException(
                sprintf('Error finding all records in table %s: %s', $this->table, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Create a new record.
     *
     * @param array $data DAta to insert.
     * @return ?object DTO object of the newly created record, or null on failure.
     * @throws RepositoryException If there's a database error.
     */
    public function create(array $data): ?object
    {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_map(fn($key) => ':' . $key, array_keys($data)));
            $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, $columns, $placeholders);
            $id = $this->database->execute($sql, $data);

            if (!$id) {
                return null;
            }

            return $this->find($id);
        } catch (\Throwable $e) {
            throw new RepositoryException(
                sprintf('Error creating record in table %s: %s', $this->table, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Update an existing record by ID.
     *
     * @param int|string $id
     * @param array $data Data to update.
     * @return ?object DTO object of the updated record, or null if not found or update fails.
     * @throws RepositoryException If there's a database error.
     */
    public function update(int|string $id, array $data): ?object
    {
        try {
            $setClauses = implode(', ', array_map(fn($key) => $key . '= :' . $key, array_keys($data)));
            $sql = sprintf('UPDATE %s SET %s WHERE id = :id', $this->table, $setClauses);
            $params = array_merge($data, ['id' => $id]);
            $updatedRows = $this->database->execute($sql, $params);

            if ($updatedRows === 0) {
                return null;
            }

            return $this->find($id);
        } catch (\Throwable $e) {
            throw new RepositoryException(
                sprintf('Error updating record with ID %s in table %s: %s', $id, $this->table, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Delete a record by ID.
     *
     * @param int|string $id
     * @return bool True if deleted, false otherwise.
     * @throws RepositoryException If there's a database error.
     */
    public function delete(int|string $id): bool
    {
        try {
            $sql = sprintf('DELETE FROM %s WHERE id = :id', $this->table);
            $params = ['id' => $id];
            $deletedRows = $this->database->execute($sql, $params);

            return $deletedRows > 0;
        } catch (\Throwable $e) {
            throw new RepositoryException(
                sprintf('Error deleting record with ID %s from table %s: %s', $id, $this->table, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Find records based on where clause.
     *
     * @param array $criteria Associative array of where clauses (column => value).
     *                         Example: ['category_id' => 1, 'is_published' => true]
     * @return array<object> Array of DTO objects matching the criteria.
     * @throws RepositoryException If there's a database error.
     */
    public function where(array $criteria): array
    {
        try {
            $whereClauses = [];
            $params = [];
            foreach ($criteria as $column => $value) {
                $placeholder = ':' . $column . '_' . count($params);
                $whereClauses[] = $column . ' = ' . $placeholder;
                $params[$placeholder] = $value;
            }
            $whereSql = implode(' AND ', $whereClauses);
            $sql = sprintf('SELECT * FROM %s WHERE %s', $this->table, $whereSql);
            $results = $this->database->query($sql, $params);


            $dtos = [];
            foreach ($results as $data) {
                $dtos[] = $this->createDtoFromData($data);
            }

            return $dtos;

        } catch (\Throwable $e) {
            throw new RepositoryException(
                sprintf('Error finding records with criteria in table %s: %s', $this->table, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Helper function to create a DTO object from data.
     *
     * @param array $data
     * @return object
     * @throws RepositoryException If the DTO class is invalid or instantiation fails.
     */
    public function createDtoFromData(array $data): object
    {
        $dtoClass = $this->dtoClass;

        if (!class_exists($dtoClass)) {
            throw new RepositoryException(sprintf('Invalid DTO class defined: %s in %s.', $dtoClass, static::class));
        }

        try {
            return new $dtoClass($data);
        } catch (\Throwable $e) {
            throw new RepositoryException(
                sprintf('Failed to instantiate DTO class %s: %s', $dtoClass, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Get the table name for this repository
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the DTO class name for this repository
     *
     * @return string
     */
    public function getDtoClass(): string
    {
        return $this->dtoClass;
    }
}