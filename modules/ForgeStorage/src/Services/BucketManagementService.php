<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Services;

use App\Modules\ForgeStorage\Dto\BucketDto;
use App\Modules\ForgeStorage\Repositories\BucketRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\UUID;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;

#[Service]
#[Provides]
#[Requires]
final class BucketManagementService
{
    public function __construct(
        private BucketRepository $bucketRepository,
        private FileSystemStorageService $fileStorage
    ) {
    }

    public function findBucketById(string $id): ?BucketDto
    {
        return $this->bucketRepository->findById($id);
    }

    public function findBucketByName(string $name): ?BucketDto
    {
        return $this->bucketRepository->findByName($name);
    }

    public function createBucket(string $name, array $config = []): bool
    {
        if ($this->fileStorage->createBucket($name)) {
            $this->bucketRepository->create([
                'id' => UUID::generate(),
                'name' => $name
            ]);
            return true;
        }
        return false;
    }

    public function createBucketRecord(array $data): int|false
    {
        return $this->bucketRepository->create($data);
    }

    public function updateBucketRecord(int $id, array $data): int
    {
        return $this->bucketRepository->update($id, $data);
    }

    public function deleteBucketRecord(int $id): int
    {
        return $this->bucketRepository->delete($id);
    }

    public function listBucketsFromDatabase(): array
    {
        return $this->bucketRepository->findAll();
    }
}
