<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Repositories;

use App\Modules\ForgeStorage\Dto\BucketDto;
use App\Modules\ForgeStorage\Models\Bucket;
use Forge\Core\Repository\BaseRepository;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Url;

#[Service]
final class BucketRepository extends BaseRepository
{
    public function __construct(protected QueryBuilder $queryBuilder)
    {
        parent::__construct($queryBuilder, Bucket::class, BucketDto::class);
    }

    /** @return array<BucketDto> */
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
        ->get(BucketDto::class);
    }

    public function findByName(string $bucket): ?BucketDto
    {
        return $this->queryBuilder
        ->select("*")
        ->where("name", "=", $bucket)
        ->first(BucketDto::class);
    }

    public function findById(mixed $id): ?BucketDto
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
}
