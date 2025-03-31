<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Services;

use App\Modules\ForgeStorage\Contracts\StorageDriverInterface;
use App\Modules\ForgeStorage\Contracts\StorageInterface;
use App\Modules\ForgeStorage\Dto\BucketDto;
use App\Modules\ForgeStorage\Dto\StorageDto;
use App\Modules\ForgeStorage\Dto\TemporaryUrlDto;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;

#[Service]
#[Provides(interface: StorageInterface::class, version: '0.1.0')]
#[Provides(interface: StorageDriverInterface::class, version: '0.1.0')]
#[Requires]
final class StorageService implements StorageInterface
{
    public function __construct(
        private FileSystemStorageService $fileStorage,
        private DatabaseStorageService $databaseStorage,
        private BucketManagementService $bucketManager,
        private TemporaryUrlService $temporaryUrlManager
    ) {
    }

    public function put(string $bucket, string $path, $contents, array $options = []): bool
    {
        return $this->fileStorage->put($bucket, $path, $contents, $options);
    }

    public function get(string $bucket, string $path)
    {
        return $this->fileStorage->get($bucket, $path);
    }

    public function getPublicFile(string $bucket, string $path)
    {
        return "$bucket/$path";
    }

    public function delete(string $bucket, string $path): bool
    {
        return $this->fileStorage->delete($bucket, $path);
    }

    public function exists(string $bucket, string $path): bool
    {
        return $this->fileStorage->exists($bucket, $path);
    }

    public function getUrl(string $bucket, string $path): string
    {
        return $this->fileStorage->getUrl($bucket, $path);
    }

    public function listBuckets(): array
    {
        return $this->fileStorage->listBuckets();
    }

    public function createBucket(string $name, array $config = []): bool
    {
        return $this->bucketManager->createBucket($name, $config);
    }

    public function findStorageRecordsByBucket(string $bucket): ?array
    {
        return $this->databaseStorage->findStorageRecordsByBucket($bucket);
    }

    public function getBucketPath(string $bucket): string
    {
        return $this->fileStorage->getBucketPath($bucket);
    }

    public function findBucketById(string $id): ?BucketDto
    {
        return $this->bucketManager->findBucketById($id);
    }

    public function findBucketByName(string $name): ?BucketDto
    {
        return $this->bucketManager->findBucketByName($name);
    }

    public function createBucketRecord(array $data): int|false
    {
        return $this->bucketManager->createBucketRecord($data);
    }

    public function updateBucketRecord(int $id, array $data): int
    {
        return $this->bucketManager->updateBucketRecord($id, $data);
    }

    public function deleteBucketRecord(int $id): int
    {
        return $this->bucketManager->deleteBucketRecord($id);
    }

    public function listBucketsFromDatabase(): array
    {
        return $this->bucketManager->listBucketsFromDatabase();
    }

    public function countBuckets(): int
    {
        return $this->databaseStorage->countBucketFiles('uploads');
    }

    public function findStorageById(string $id): ?StorageDto
    {
        return $this->databaseStorage->findStorageById($id);
    }

    public function createStorageRecord(string $bucketId, string $bucketName, string $path, int $size, string $mimeType, ?string $expiresAt = null): int|false
    {
        return $this->databaseStorage->createStorageRecord($bucketId, $bucketName, $path, $size, $mimeType, $expiresAt);
    }

    public function updateStorageRecord(int $id, array $data): int
    {
        return $this->databaseStorage->updateStorageRecord($id, $data);
    }

    public function deleteStorageRecord(int $id): int
    {
        return $this->databaseStorage->deleteStorageRecord($id);
    }

    public function temporaryUrl(string $bucket, string $path, int $expires, string $file): string
    {
        return $this->temporaryUrlManager->createTemporaryUrl($bucket, $path, $expires, $file);
    }

    public function findTemporaryUrlByCleanPath(string $cleanPath): ?TemporaryUrlDto
    {
        return $this->temporaryUrlManager->findTemporaryUrlByCleanPath($cleanPath);
    }

    public function deleteExpiredTemporaryUrls(): int
    {
        return $this->temporaryUrlManager->deleteExpiredTemporaryUrls();
    }
}
