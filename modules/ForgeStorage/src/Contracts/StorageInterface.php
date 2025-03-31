<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Contracts;

use App\Modules\ForgeStorage\Dto\BucketDto;
use App\Modules\ForgeStorage\Dto\StorageDto;
use App\Modules\ForgeStorage\Dto\TemporaryUrlDto;

interface StorageInterface
{
    public function put(string $bucket, string $path, $contents, array $options = []): bool;

    public function get(string $bucket, string $path);

    public function delete(string $bucket, string $path): bool;

    public function exists(string $bucket, string $path): bool;

    public function getUrl(string $bucket, string $path): string;

    public function listBuckets(): array;

    public function createBucket(string $name, array $config = []): bool;

    public function findBucketById(string $id): ?BucketDto;

    public function createBucketRecord(array $data): int|false;

    public function updateBucketRecord(int $id, array $data): int;

    public function deleteBucketRecord(int $id): int;

    public function listBucketsFromDatabase(): array;

    public function findStorageById(string $id): ?StorageDto;

    public function createStorageRecord(string $bucketId, string $bucketName, string $path, int $size, string $mimeType, ?string $expiresAt = null): int|false;

    public function updateStorageRecord(int $id, array $data): int;

    public function deleteStorageRecord(int $id): int;

    public function temporaryUrl(string $bucket, string $path, int $expires, string $file): string;

    public function findTemporaryUrlByCleanPath(string $cleanPath): ?TemporaryUrlDto;

    public function deleteExpiredTemporaryUrls(): int;
}
