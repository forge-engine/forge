<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use Forge\Core\Contracts\Database\{DatabaseConnectionInterface, QueryBuilderInterface};
use PDOStatement;

final class QueryBuilder implements QueryBuilderInterface
{
    public function __construct(
        private DatabaseConnectionInterface $conn,
        private string                      $table = '',
        private array                       $select = [],
        private array                       $where = [],
        private array                       $params = [],
        private ?string                     $order = null,
        private ?int                        $limit = null,
        private ?int                        $offset = null,
        private bool                        $forUpdate = false
    )
    {
    }

    public function table(?string $name): self
    {
        return new self($this->conn, table: $name, select: $this->select, where: $this->where,
            params: $this->params, order: $this->order, limit: $this->limit,
            offset: $this->offset, forUpdate: $this->forUpdate);
    }

    public function select(string ...$cols): self
    {
        return new self($this->conn, table: $this->table, select: $cols, where: $this->where,
            params: $this->params, order: $this->order, limit: $this->limit,
            offset: $this->offset, forUpdate: $this->forUpdate);
    }

    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            return $this->where('1', '=', '0');
        }
        $keys = [];
        $newParams = $this->params;

        foreach ($values as $v) {
            $key = ':p' . count($newParams);
            $keys[] = $key;
            $newParams[$key] = $v;
        }

        $where = [...$this->where, "$column IN (" . implode(',', $keys) . ")"];
        return new self($this->conn, table: $this->table, select: $this->select,
            where: $where, params: $newParams, order: $this->order,
            limit: $this->limit, offset: $this->offset, forUpdate: $this->forUpdate);
    }

    public function where(string $column, string $operator, mixed $value = null): self
    {
        if ($value === null) {
            $where = [...$this->where, "$column $operator"];
            return new self($this->conn, table: $this->table, select: $this->select,
                where: $where, params: $this->params, order: $this->order,
                limit: $this->limit, offset: $this->offset, forUpdate: $this->forUpdate);
        }
        $key = ':p' . count($this->params);
        $where = [...$this->where, "$column $operator $key"];
        $params = [...$this->params, $key => $value];
        return new self($this->conn, table: $this->table, select: $this->select,
            where: $where, params: $params, order: $this->order,
            limit: $this->limit, offset: $this->offset, forUpdate: $this->forUpdate);
    }

    public function whereNotIn(string $column, array $values): self
    {
        if ($values === []) {
            return $this;
        }
        $keys = [];
        $newParams = $this->params;

        foreach ($values as $v) {
            $keys[] = $key = ':p' . count($this->params);
            $key = ':p' . count($newParams);
        }
        $where = [...$this->where, "$column NOT IN (" . implode(',', $keys) . ")"];
        return new self($this->conn, table: $this->table, select: $this->select,
            where: $where, params: $newParams, order: $this->order,
            limit: $this->limit, offset: $this->offset, forUpdate: $this->forUpdate);
    }

    public function whereNull(string $column): self
    {
        return $this->where($column, 'IS', 'NULL');
    }

    public function whereNotNull(string $column): self
    {
        return $this->where($column, 'IS NOT', 'NULL');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        return new self($this->conn, table: $this->table, select: $this->select,
            where: $this->where, params: $this->params, order: "$column $direction",
            limit: $this->limit, offset: $this->offset, forUpdate: $this->forUpdate);
    }

    public function offset(int $count): self
    {
        return new self($this->conn, table: $this->table, select: $this->select,
            where: $this->where, params: $this->params, order: $this->order,
            limit: $this->limit, offset: $count, forUpdate: $this->forUpdate);
    }

    public function lockForUpdate(): self
    {
        return new self($this->conn, table: $this->table, select: $this->select,
            where: $this->where, params: $this->params, order: $this->order,
            limit: $this->limit, offset: $this->offset, forUpdate: true);
    }

    public function getRaw(): array
    {
        return $this->get();
    }


    public function get(): array
    {
        return $this->run()->fetchAll();
    }

    private function run(): PDOStatement
    {
        $sql = $this->buildSelect();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($this->params);
        return $stmt;
    }

    private function buildSelect(): string
    {
        $sql = 'SELECT ' . ($this->select === [] ? '*' : implode(', ', $this->select))
            . " FROM {$this->table}";

        if ($this->where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }
        if ($this->order !== null) {
            $sql .= " ORDER BY {$this->order}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null && $this->offset > 0) {
            $sql .= " OFFSET {$this->offset}";
        }

        if ($this->forUpdate && in_array($this->conn->getDriver(), ['mysql', 'pgsql'], true)) {
            $sql .= ' FOR UPDATE';
        }

        return $sql;
    }

    public function execute(string $sql): void
    {
        $this->conn->exec($sql);
    }

    public function insert(array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $vals = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ($cols) VALUES ($vals)";
        $this->conn->prepare($sql)->execute($data);
        return (int)$this->conn->getPdo()->lastInsertId();
    }

    public function update(array $data): int
    {
        $set = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = :u_$col";
            $this->params[':u_' . $col] = $val;
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . $this->buildWhere();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    private function buildWhere(): string
    {
        return $this->where === [] ? '' : ' WHERE ' . implode(' AND ', $this->where);
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}" . $this->buildWhere();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    public function count(string $column = '*'): int
    {
        return (int)$this->aggregate("COUNT($column)");
    }

    private function aggregate(string $fn): mixed
    {
        $sql = "SELECT {$fn} FROM {$this->table}" . $this->buildWhere();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetchColumn();
    }

    public function sum(string $column): float
    {
        return (float)$this->aggregate("SUM($column)");
    }


    public function avg(string $column): float
    {
        return (float)$this->aggregate("AVG($column)");
    }

    public function min(string $column): float
    {
        return (float)$this->aggregate("MIN($column)");
    }

    public function max(string $column): float
    {
        return (float)$this->aggregate("MAX($column)");
    }

    public function reset(): self
    {
        return new self($this->conn);
    }


    public function whereRaw(string $sql, array $params = []): self
    {
        return $this;
    }

    public function leftJoin(string $t, string $a, string $op, string $b): self
    {
        return $this;
    }

    public function join(string $t, string $a, string $op, string $b, string $type = 'INNER'): self
    {
        return $this;
    }

    public function rightJoin(string $t, string $a, string $op, string $b): self
    {
        return $this;
    }

    public function groupBy(string ...$cols): self
    {
        return $this;
    }

    public function having(string $col, string $op, mixed $val): self
    {
        return $this;
    }

    public function exists(): bool
    {
        return $this->first() !== null;
    }

    public function first(): ?array
    {
        $stmt = $this->limit(1)->run();
        return $stmt->fetch() ?: null;
    }

    public function limit(int $n): self
    {
        return new self($this->conn, table: $this->table, select: $this->select,
            where: $this->where, params: $this->params, order: $this->order,
            limit: $n, offset: $this->offset, forUpdate: $this->forUpdate);
    }

    public function transaction(callable $cb): mixed
    {
        return $cb($this);
    }

    public function beginTransaction(): self
    {
        $this->conn->beginTransaction();
        return $this;
    }

    public function inTransaction(): bool
    {
        return $this->conn->getPdo()->inTransaction();
    }

    public function commit(): self
    {
        $this->conn->commit();
        return $this;
    }

    public function rollback(): self
    {
        $this->conn->rollBack();
        return $this;
    }

    public function getConnection(): DatabaseConnectionInterface
    {
        return $this->conn;
    }


    public function createTableFromAttributes(string $t, array $c, array $i = []): string
    {
        return '';
    }

    public function createTable(string $n, array $c, bool $i = false): string
    {
        return '';
    }

    public function createIndex(string $n, array $c, bool $u = false): string
    {
        return '';
    }

    public function dropTable(string $n): string
    {
        return '';
    }

    public function getSql(): string
    {
        return '';
    }

    public function setTable(string $table): QueryBuilderInterface
    {
        return new self(
            $this->conn,
            table: $table,
            select: $this->select,
            where: $this->where,
            params: $this->params,
            order: $this->order,
            limit: $this->limit,
            offset: $this->offset,
            forUpdate: $this->forUpdate
        );
    }

    public function find(int $id): ?array
    {
        // TODO: Implement find() method.
        return [];
    }
}