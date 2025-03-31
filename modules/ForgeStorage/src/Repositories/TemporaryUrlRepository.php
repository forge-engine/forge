<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Repositories;

use App\Modules\ForgeStorage\Dto\TemporaryUrlDto;
use App\Modules\ForgeStorage\Models\TemporaryUrl;
use Forge\Core\Repository\BaseRepository;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Url;

#[Service]
final class TemporaryUrlRepository extends BaseRepository
{
    public function __construct(protected QueryBuilder $queryBuilder)
    {
        parent::__construct($queryBuilder, TemporaryUrl::class, TemporaryUrlDto::class);
    }

    /** @return array<TemporaryUrlDto> */
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
        ->get(TemporaryUrlDto::class);
    }

    public function findByCleanPath(string $cleanPath): ?TemporaryUrlDto
    {
        return $this->queryBuilder
        ->select("*")
        ->where("clean_path", "=", $cleanPath)
        ->first(TemporaryUrlDto::class);
    }


    public function findById(mixed $id): ?TemporaryUrlDto
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
