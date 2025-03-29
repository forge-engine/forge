<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Repositories;

use App\Modules\ForgeAuth\Dto\UserDto;
use App\Modules\ForgeAuth\Models\User;
use Forge\Core\Repository\BaseRepository;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Url;

#[Service]
final class UserRepository extends BaseRepository
{
    public function __construct(QueryBuilder $queryBuilder)
    {
        parent::__construct($queryBuilder, User::class, UserDto::class);
    }

    /** @return array<UserDto> */
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
        ->get(UserDto::class);
    }

    public function findById(int $id): ?UserDto
    {
        return parent::findById($id);
    }

    public function findByEmail(string $email): ?UserDto
    {
        return $this->queryBuilder
            ->where("email", "=", $email)
            ->first(UserDto::class);
    }

    public function create(array $data): int|false
    {
        return parent::create($data);
    }

    public function update(int $id, array $data): int
    {
        return parent::update($id, $data);
    }

    public function delete(int $id): int
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
