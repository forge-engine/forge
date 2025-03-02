<?php

namespace Forge\Modules\ForgeStorage\Contracts;

interface StorageInterface
{
    public function put(string $bucket, string $path, $contents, array $options = []): bool;

    public function get(string $bucket, string $path);

    public function delete(string $bucket, string $path): bool;

    public function exists(string $bucket, string $path): bool;

    public function getUrl(string $bucket, string $path): string;

    public function temporaryUrl(string $bucket, string $path, int $expires): string;

    public function listBuckets(): array;

    public function createBucket(string $name, array $config = []): bool;
}