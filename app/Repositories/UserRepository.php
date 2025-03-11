<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Dto\UserDto;
use App\Models\User;
use Forge\Core\Repository\BaseRepository;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;

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
}
