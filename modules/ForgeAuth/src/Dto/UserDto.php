<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Dto;

use Forge\Core\Dto\Attributes\Sanitize;
use Forge\Core\Dto\BaseDto;
use Forge\Traits\DTOHelper;

#[Sanitize(properties: ['password'])]
final class UserDto extends BaseDto
{
    use DTOHelper;

    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public ?string $password,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
        public ?\DateTimeImmutable $deleted_at = null
    ) {
    }
}
