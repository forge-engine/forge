<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Repositories;

use App\Modules\ForgeStorage\Dto\StorageDto;
use App\Modules\ForgeStorage\Models\Storage;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;

#[Service(singleton: false)]
final class StorageRepository
{
    public function __construct(protected QueryBuilderInterface $queryBuilder)
    {
        //parent::__construct($queryBuilder, Storage::class, StorageDto::class);
    }

    /** @return array<StorageDto> */
    public function findAll(): array
    {
        //return parent::findAll();
        throw new \Exception('Not implemented');
    }

    public function find(int $limit, int $offset): array
    {
        return $this->queryBuilder
            ->select("*")
            ->limit($limit)
            ->offset($offset)
            ->orderBy('created_at', 'ASC')
            ->get(StorageDto::class);
    }

    public function countFilesBucket(string $bucket): int
    {
        return $this->queryBuilder
            ->where("bucket", "=", $bucket)
            ->count();
    }

    public function findStorageRecordsByBucket(string $bucket, int $limit = 20, $offset = 0): array
    {
        return $this->queryBuilder
            ->select("*")
            ->where("bucket", "=", $bucket)
            ->limit($limit)
            ->offset($offset)
            ->orderBy('created_at', 'ASC')
            ->get(StorageDto::class);
    }

    public function findById(mixed $id): ?StorageDto
    {
        //return parent::findById($id);
        throw new \Exception('Not implemented');
    }

    public function create(array $data): int|false
    {
        //return parent::create($data);
        throw new \Exception('Not implemented');
    }

    public function update(mixed $id, array $data): int
    {
        //return parent::update($id, $data);
        throw new \Exception('Not implemented');
    }

    public function delete(mixed $id): int
    {
        //return parent::delete($id);
        throw new \Exception('Not implemented');
    }
}
