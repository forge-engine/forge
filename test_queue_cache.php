<?php

define('BASE_PATH', __DIR__);

require_once __DIR__ . '/engine/Core/Autoloader.php';
\Forge\Core\Autoloader::register();
\Forge\Core\Autoloader::addPath('App\\Modules\\ForgeEvents', BASE_PATH . '/modules/ForgeEvents/src');

// Load helpers
require_once __DIR__ . '/engine/Core/Support/helpers.php';

use Forge\Core\DI\Container;
use Forge\Core\Config\Config;
use App\Modules\ForgeEvents\Services\QueueHubService;
use Forge\Core\Cache\CacheManager;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;

// Setup Container
$container = Container::getInstance();

// Mock Config
$config = new Config(BASE_PATH . '/config');
// Manually inject cache config since we might not have the files or want to override
$reflection = new ReflectionClass($config);
$prop = $reflection->getProperty('config');
$prop->setAccessible(true);
$prop->setValue($config, [
    'cache' => [
        'default' => 'sqlite',
        'drivers' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => __DIR__ . '/storage/database/cache.sqlite',
                'table' => 'cache',
            ]
        ]
    ]
]);

$container->singleton(Config::class, function () use ($config) {
    return $config;
});

// Register CacheManager
$container->singleton(CacheManager::class, function ($c) {
    return new CacheManager('sqlite');
});

// Register QueueHubService
$container->register(QueueHubService::class);

// Mock QueryBuilderInterface
$container->bind(QueryBuilderInterface::class, function () {
    return new class implements QueryBuilderInterface {
        public function lockForUpdate(): self { return $this; }
        public function getConnection(): DatabaseConnectionInterface {
            return new class implements DatabaseConnectionInterface {
                public function getPdo(): \PDO { return new \PDO('sqlite::memory:'); }
                public function beginTransaction(): bool { return true; }
                public function commit(): bool { return true; }
                public function rollBack(): bool { return true; }
                public function inTransaction(): bool { return false; }
                public function lastInsertId(?string $name = null): string { return '1'; }
                public function prepare(string $sql): \PDOStatement { return new \PDOStatement(); }
                public function query(string $sql): \PDOStatement { return new \PDOStatement(); }
                public function exec(string $sql): int { return 1; }
            };
        }
        public function setTable(string $table): self { return $this; }
        public function select(string ...$columns): self { return $this; }
        public function whereRaw(string $sql, array $params = []): self { return $this; }
        public function selectRaw(string $expression, array $params = []): self { return $this; }
        public function whereNull(string $column): self { return $this; }
        public function whereNotNull(string $column): self { return $this; }
        public function orderBy(string $column, string $direction = "ASC"): self { return $this; }
        public function limit(int $count): self { return $this; }
        public function offset(int $count): self { return $this; }
        public function createTableFromAttributes(string $table, array $columns, array $indexes = []): string { return ''; }
        public function get(): array { return [['queue' => 'default', 'id' => 1, 'payload' => 'serialized_data']]; }
        public function execute(string $sql): void {}
        public function getRaw(): array { return []; }
        public function insert(array $data): int { return 1; }
        public function update(array $data): int { return 1; }
        public function delete(): int { return 1; }
        public function find(int $id): ?array { return null; }
        public function where(string $column, string $operator, mixed $value): self { return $this; }
        public function whereIn(string $column, array $values): self { return $this; }
        public function whereNotIn(string $column, array $values): self { return $this; }
        public function first(): ?array { return null; }
        public function leftJoin(string $table, string $first, string $operator, string $second): self { return $this; }
        public function join(string $table, string $first, string $operator, string $second, string $type = "INNER"): self { return $this; }
        public function rightJoin(string $table, string $first, string $operator, string $second): self { return $this; }
        public function groupBy(string ...$columns): self { return $this; }
        public function having(string $column, string $operator, mixed $value): self { return $this; }
        public function exists(): bool { return false; }
        public function reset(): self { return $this; }
        public function transaction(callable $callback): mixed { return $callback($this); }
        public function beginTransaction(): self { return $this; }
        public function inTransaction(): bool { return false; }
        public function commit(): self { return $this; }
        public function rollback(): self { return $this; }
        public function count(string $column = "*"): int { return 10; }
        public function sum(string $column): float { return 0.0; }
        public function avg(string $column): float { return 0.0; }
        public function max(string $column): float { return 0.0; }
        public function min(string $column): float { return 0.0; }
        public function table(?string $name): string|self { return $this; }
        public function createTable(string $tableName, array $columns, bool $ifNotExists = false): string { return ''; }
        public function createIndex(string $indexName, array $columns, bool $unique = false): string { return ''; }
        public function dropTable(string $tableName): string { return ''; }
        public function getSql(): string { return ''; }
    };
});

echo "Resolving QueueHubService...\n";
try {
    $service = $container->make(QueueHubService::class);
    echo "Service class: " . get_class($service) . "\n";

    if (strpos(get_class($service), 'Forge\\Cache\\Proxy') !== false) {
        echo "SUCCESS: QueueHubService is proxied.\n";
    } else {
        echo "FAILURE: QueueHubService is NOT proxied.\n";
    }

    // Call getQueues which caches 'queue_names'
    echo "Calling getQueues...\n";
    $result = $service->getQueues();
    echo "Called getQueues. Result count: " . count($result) . "\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
