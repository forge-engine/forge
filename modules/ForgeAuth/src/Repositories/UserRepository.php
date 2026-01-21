<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Repositories;

use App\Modules\ForgeAuth\Contracts\UserRepositoryInterface;
use App\Modules\ForgeAuth\Dto\CreateUserData;
use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use App\Modules\ForgeSqlOrm\ORM\RecordRepository;
use Forge\Core\Cache\Attributes\Cache;
use Forge\Core\Cache\Attributes\NoCache;
use Forge\Traits\CacheLifecycleHooks;

#[NoCache]
class UserRepository extends RecordRepository implements UserRepositoryInterface
{
  use CacheLifecycleHooks;

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

  #[Cache(key: 'find_by_{id}', ttl: 3600)]
  public function findById(int $id): ?User
  {
    return parent::find($id);
  }

  #[Cache(key: 'pagination_{page}_{perPage}', ttl: 3600)]
  public function paginate(int $page = 1, int $perPage = 10, array $options = []): Paginator
  {
    return parent::paginate($page, $perPage, $options);
  }

  #[Cache(key: 'user_identifier_{identifier}', ttl: 3600)]
  public function findByIdentifier(string $identifier): ?User
  {
    return User::query()->where('identifier', '=', $identifier)->first();
  }

  #[Cache(key: 'user_email_{email}', ttl: 3600)]
  public function findByEmail(string $email): ?User
  {
    return User::query()->where('email', '=', $email)->first();
  }
}

