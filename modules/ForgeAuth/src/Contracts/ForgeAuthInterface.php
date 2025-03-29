<?php

namespace App\Modules\ForgeAuth\Contracts;

use App\Modules\ForgeAuth\Dto\UserDto;

interface ForgeAuthInterface
{
    public function register(array $credentials): bool;
    public function login(array $credentials): UserDTO;
    public function logout(): void;
    public function user(): ?UserDTO;
}
