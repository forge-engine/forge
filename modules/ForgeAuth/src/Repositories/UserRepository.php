<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Repositories;

use App\Modules\ForgeAuth\Contracts\UserRepositoryInterface;
use App\Modules\ForgeAuth\Dto\CreateUserData;
use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeSqlOrm\ORM\Cache\QueryCache;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use App\Modules\ForgeSqlOrm\ORM\RecordRepository;

final class UserRepository extends RecordRepository implements UserRepositoryInterface
{
  protected function getModelClass(): string
  {
    return User::class;
  }

  public function create(mixed $data): User
  {
    if ($data instanceof CreateUserData) {
      $user = new User();
      $user->identifier = $data->identifier;
      $user->email = $data->email;
      $user->password = $data->password;
      $user->status = $data->status;
      $user->metadata = $data->metadata;
      $user->save();

      $this->cache->invalidate($this->tableName);

      return $user;
    }

    return parent::create($data);
  }

  public function findById(int $id): ?User
  {
    return parent::find($id);
  }

  public function paginate(int $page = 1, int $perPage = 10, array $options = []): Paginator
  {
    return parent::paginate($page, $perPage, $options);
  }

  public function findByIdentifier(string $identifier): ?User
  {
    $key = $this->cache->generateKey($this->tableName, 'findByIdentifier', $identifier);
    $cached = $this->cache->get($key);

    if ($cached !== null) {
      return $cached;
    }

    $user = User::query()->where('identifier', '=', $identifier)->first();

    if ($user !== null) {
      $this->cache->set($key, $user);
    }

    return $user;
  }

  public function findByEmail(string $email): ?User
  {
    $key = $this->cache->generateKey($this->tableName, 'findByEmail', $email);
    $cached = $this->cache->get($key);

    if ($cached !== null) {
      return $cached;
    }

    $user = User::query()->where('email', '=', $email)->first();

    if ($user !== null) {
      $this->cache->set($key, $user);
    }

    return $user;
  }
}

