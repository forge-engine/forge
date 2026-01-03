<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Contracts;

use App\Modules\ForgeAuth\Dto\CreateUserData;
use App\Modules\ForgeAuth\Models\User;

interface UserRepositoryInterface
{
    public function create(CreateUserData $data): User;
    
    public function findById(int $id): ?User;
    
    public function findByIdentifier(string $identifier): ?User;
    
    public function findByEmail(string $email): ?User;
}

