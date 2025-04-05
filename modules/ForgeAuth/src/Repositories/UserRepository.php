<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Repositories;

use App\Modules\ForgeAuth\Dto\UserDto;
use App\Modules\ForgeAuth\Models\User;
use Forge\Core\Repository\BaseRepository;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class UserRepository extends BaseRepository
{
    protected array $searchableFields = ['username', 'email'];

    public function __construct(protected QueryBuilder $queryBuilder)
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
        return parent::find($limit, $offset);
    }

    public function findById(mixed $id): ?UserDto
    {
        return parent::findById($id);
    }

    public function findByEmail(string $email): ?UserDto
    {
        return parent::findByProperty("email", $email);
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
