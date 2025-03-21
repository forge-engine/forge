<?php

declare(strict_types=1);

namespace App\Dto;

use Forge\Traits\DTOHelper;

final readonly class UserDto
{
    use DTOHelper;
    
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public string $password,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
        public ?\DateTimeImmutable $deleted_at = null
    ) {}
}
