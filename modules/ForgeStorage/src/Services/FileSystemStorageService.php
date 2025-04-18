<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Services;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use Forge\Traits\FileHelper;

#[Service]
#[Provides]
#[Requires]
final class FileSystemStorageService
{
    use FileHelper;

    private string $root;
    private string $publicPath;

    public function __construct(
        private Config $config,
    ) {
        $storageConfig = $this->config->get('forge_storage', []);
        $this->root = BASE_PATH . '/' . ($storageConfig['root_path'] ?? 'storage/app');
        $this->publicPath = BASE_PATH . '/' . ($storageConfig['public_path'] ?? 'public/storage');
    }

    public function put(string $bucket, string $path, $contents, array $options = []): bool
    {
        $fullPath = $this->getBucketPath($bucket) . '/' . $path;
        $this->ensureDirectoryExists(dirname($fullPath));

        return file_put_contents($fullPath, $contents) !== false;
    }

    public function get(string $bucket, string $path)
    {
        return file_get_contents($this->getBucketPath($bucket) . '/' . $path);
    }

    public function delete(string $bucket, string $path): bool
    {
        $fullPath = $this->getBucketPath($bucket) . '/' . $path;
        return file_exists($fullPath) && unlink($fullPath);
    }

    public function exists(string $bucket, string $path): bool
    {
        return file_exists($this->getBucketPath($bucket) . '/' . $path);
    }

    public function getUrl(string $bucket, string $path): string
    {
        $bucketConfig = $this->getBucketConfig($bucket);
        return $bucketConfig['public']
            ? $this->publicPath . "/{$bucket}/{$path}"
            : "/storage/{$bucket}/{$path}";
    }

    public function listBuckets(): array
    {
        return array_filter(scandir($this->root), fn ($dir) => !in_array($dir, ['.', '..']));
    }

    public function createBucket(string $name, array $config = []): bool
    {
        $path = $this->root . '/' . $name;
        if (!file_exists($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }

    public function getBucketPath(string $bucket): string
    {
        return "{$this->root}/{$bucket}";
    }

    private function getBucketConfig(string $bucket): array
    {
        $configFile = $this->getBucketPath($bucket) . '/.config';
        return file_exists($configFile)
            ? json_decode(file_get_contents($configFile), true)
            : ['public' => false, 'expire' => null];
    }
}
