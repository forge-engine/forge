<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Services;

use App\Modules\ForgeStorage\Dto\StorageDto;
use App\Modules\ForgeStorage\Repositories\StorageRepository;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\UUID;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;

#[Service]
#[Provides]
#[Requires]
final class DatabaseStorageService
{
    public function __construct(
        private Config $config,
        private StorageRepository $storageRepository
    ) {
    }

    public function findStorageById(string $id): ?StorageDto
    {
        return $this->storageRepository->findById($id);
    }

    public function countBucketFiles(string $bucket): int
    {
        return $this->storageRepository->countFilesBucket($bucket);
    }

    public function findStorageRecordsByBucket(string $bucket): ?array
    {
        return $this->storageRepository->findStorageRecordsByBucket($bucket);
    }

    public function createStorageRecord(string $bucketId, string $bucketName, string $path, int $size, string $mimeType, ?string $expiresAt = null): int|false
    {
        return $this->storageRepository->create([
            'id' => UUID::generate(),
            'bucket_id' => $bucketId,
            'bucket' => $bucketName,
            'path' => $path,
            'size' => $size,
            'mime_type' => $mimeType,
            'expires_at' => $expiresAt,
        ]);
    }

    public function updateStorageRecord(int $id, array $data): int
    {
        return $this->storageRepository->update($id, $data);
    }

    public function deleteStorageRecord(int $id): int
    {
        return $this->storageRepository->delete($id);
    }
}
