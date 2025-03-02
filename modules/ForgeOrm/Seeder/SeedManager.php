<?php

namespace Forge\Modules\ForgeOrm\Seeder;

use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\Database\Contracts\DatabaseInterface;
use Forge\Modules\ForgeOrm\Schema\Blueprint;
use Forge\Modules\ForgeOrm\Schema\Schema;

class SeedManager
{
    use OutputHelper;

    private const SEEDS_TABLE = 'forge_seeds';

    private DatabaseInterface $db;
    private Schema $schema;

    public function __construct(DatabaseInterface $db, Schema $schema)
    {
        $this->db = $db;
        $this->schema = $schema;
        $this->ensureSeedsTableExists();
    }

    public function runSeeds(bool $refresh = false, ?string $specificClass = null): void
    {
        $env = App::env('APP_ENV');
        if ($env === 'production') {
            $this->error('Seeder cant run in production');
            exit(0);
        }

        $this->db->beginTransaction();

        try {
            if ($refresh) {
                $this->truncateSeeds();
            }

            $seeds = $specificClass
                ? [$this->findSpecificSeed($specificClass)]
                : $this->getPendingSeeds();

            foreach ($seeds as $seed) {
                $this->runSeed($seed);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function ensureSeedsTableExists(): void
    {
        if (!$this->schema->hasTable(self::SEEDS_TABLE)) {
            $this->schema->create(self::SEEDS_TABLE, function (Blueprint $table) {
                $table->integer('seed', true);
                $table->integer('batch');
                $table->timestamps();
            });
        }
    }

    private function getPendingSeeds(): array
    {
        $ran = $this->getRanSeeds();
        $all = $this->getAllSeeds();

        return array_diff_key($all, $ran);
    }

    private function getRanSeeds(): array
    {
        return $this->db->query(
            "SELECT seed FROM " . self::SEEDS_TABLE
        );
    }

    private function getAllSeeds(): array
    {
        return array_merge(
            $this->discoverModuleSeeds(),
            $this->discoverAppSeeds()
        );
    }

    private function discoverModuleSeeds(): array
    {
        $seeds = [];
        $modulesPath = BASE_PATH . '/modules/';

        foreach (glob($modulesPath . '*/Database/Seeds/*.php') as $file) {
            $seedName = $this->getSeedNameFromFilePath($file);
            $namespace = $this->getSeedNamespaceFromFilePath($file, 'Modules');
            $className = $this->getClassName($seedName, $namespace);
            $seeds[$seedName] = ['name' => $seedName, 'path' => $file, 'class' => $className, 'namespace' => $namespace];
        }

        return $seeds;
    }

    private function discoverAppSeeds(): array
    {
        $seeds = [];
        $appsPath = BASE_PATH . '/apps/';

        foreach (glob($appsPath . '*/Database/Seeds/*.php') as $file) {
            $seedName = $this->getSeedNameFromFilePath($file);
            $namespace = $this->getSeedNamespaceFromFilePath($file, 'Apps');
            $className = $this->getClassName($seedName, $namespace);
            $seeds[$seedName] = ['name' => $seedName, 'path' => $file, 'class' => $className, 'namespace' => $namespace];
        }

        return $seeds;
    }

    private function getSeedNameFromFilePath(string $path): string
    {
        return basename($path, '.php');
    }

    private function getSeedNamespaceFromFilePath(string $filePath, string $type): string
    {
        $segments = explode(DIRECTORY_SEPARATOR, $filePath);
        $moduleOrAppName = $segments[array_search('modules', $segments) + 1] ?? $segments[array_search('apps', $segments) + 1] ?? '';
        return "{$type}\\{$moduleOrAppName}\\Database\\Seeds";
    }


    private function getClassName(string $filename, string $namespace): string
    {
        $className = preg_replace("/^\d+_/", "", $filename);
        $className = str_replace("_", "", ucwords($className, "_"));
        return "{$namespace}\\{$className}"; // Combine namespace and class name
    }

    /**
     *
     * @param string $path The path to the seed file.
     * @return string The extracted class name.
     */
    private function getClassNameFromFile(string $path): string
    {
        $contents = file_get_contents($path);
        preg_match('/class\s+(\w+)\s+extends/', $contents, $matches);
        return $matches[1] ?? '';
    }

    private function runSeed(array $seed): void
    {
       
        $className = $this->getClassNameFromFile($seed['path']);

        $instance = new $className();
        $instance->run();

        $this->recordSeed($seed['name']);
    }

    private function findSpecificSeed(string $className): array
    {
        foreach ($this->getAllSeeds() as $seed) {
            if ($seed['name'] === $className) {
                return $seed;
            }
        }

        throw new \RuntimeException("Seed class $className not found");
    }

    private function truncateSeeds(): void
    {
        $this->db->execute("TRUNCATE TABLE " . self::SEEDS_TABLE);
    }

    private function recordSeed(string $seed): void
    {
        $this->db->execute(
            "INSERT INTO " . self::SEEDS_TABLE . " (seed, batch, created_at) VALUES (?, ?, ?)",
            [$seed, $this->getNextBatchNumber(), date('Y-m-d H:i:s')]
        );
    }

    private function getNextBatchNumber(): int
    {
        $result = $this->db->query(
            "SELECT MAX(batch) as max_batch FROM " . self::SEEDS_TABLE
        );

        return ($result[0]['max_batch'] ?? 0) + 1;
    }
}