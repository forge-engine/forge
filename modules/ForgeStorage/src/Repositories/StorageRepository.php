<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Repositories;

use App\Modules\ForgeStorage\Dto\StorageDto;
use App\Modules\ForgeStorage\Models\Storage;
use Forge\Core\Repository\BaseRepository;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Url;

#[Service(singleton: false)]
final class StorageRepository extends BaseRepository
{
    public function __construct(protected QueryBuilder $queryBuilder)
    {
        parent::__construct($queryBuilder, Storage::class, StorageDto::class);
    }

    /** @return array<StorageDto> */
    public function findAll(): array
    {
        return parent::findAll();
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
        return parent::findById($id);
    }

    public function create(array $data): int|false
    {
        return parent::create($data);
    }

    public function update(mixed $id, array $data): int
    {
        return parent::update($id, $data);
    }

    public function delete(mixed $id): int
    {
        return parent::delete($id);
    }

    public function paginate(int $page, int $perPage, string $baseUrl): array
    {
        $offset = ($page - 1) * $perPage;
        $users = $this->find($perPage, $offset);
        $total = $this->queryBuilder->count();
        $totalPages = (int) ceil($total / $perPage);

        $links = Url::generateLinks($baseUrl, $page, $perPage, $totalPages);

        return [
          'data' => $users,
          'meta' => [
              'total' => $total,
              'page' => $page,
              'perPage' => $perPage,
              'totalPages' => $totalPages,
              'links' => $links
          ]
        ];
    }
}
