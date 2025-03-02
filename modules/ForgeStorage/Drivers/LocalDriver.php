<?php

namespace Forge\Modules\ForgeStorage\Drivers;

use Forge\Core\Helpers\UUID;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeStorage\Contracts\StorageInterface;
use Forge\Core\Helpers\App;

class LocalDriver implements StorageInterface
{
    private string $root;
    private string $publicPath;

    private DatabaseInterface $db;

    public function __construct()
    {
        $config = App::config()->get('forge_storage');
        $this->root = BASE_PATH . '/' . $config['root_path'];
        $this->publicPath = BASE_PATH . '/' . $config['public_path'];

        // db
        $db = App::getContainer()->get(DatabaseInterface::class);
        $this->db = $db;
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

    public function temporaryUrl(string $bucket, string $path, int $expires): string
    {
        $token = hash_hmac('sha256', "{$bucket}/{$path}|{$expires}", App::config()->get('app.key'));

        // Generate a clean URL
        $cleanPath = bin2hex(random_bytes(16)) . '-' . basename($path);

        // Store the mapping in the database or cache
        $this->db->table('temporary_urls')->insert([
            'id' => UUID::generate(),
            'clean_path' => $cleanPath,
            'bucket' => $bucket,
            'path' => $path,
            'expires_at' => date('Y-m-d H:i:s', $expires),
            'token' => $token,
        ]);

        return "/files/{$cleanPath}?expires={$expires}&token={$token}";
    }

    public function listBuckets(): array
    {
        return array_filter(scandir($this->root), fn($dir) => !in_array($dir, ['.', '..']));
    }

    public function createBucket(string $name, array $config = []): bool
    {
        $path = $this->root . '/' . $name;
        if (!file_exists($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }

    private function getBucketPath(string $bucket): string
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

    private function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
}