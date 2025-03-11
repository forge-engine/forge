<?php

declare(strict_types=1);

namespace App\Dto;

use Forge\Core\Dto\BaseDto;

final readonly class UserDto extends BaseDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $password,
        public \DateTimeImmutable $createdAt
    ) {}
}
