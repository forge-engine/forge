<?php

namespace App\Modules\ForgeAuth\Contracts;

use App\Modules\ForgeAuth\Models\User;

interface ForgeAuthInterface
{
    public function register(array $credentials): bool;
    public function login(array $credentials): User;
    public function logout(): void;
    public function user(): ?User;
}
