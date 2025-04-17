<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Dto;

use Forge\Core\Dto\BaseDto;

final class UserMetadataDto extends BaseDto
{
    public function __construct(
        public ?int $referal_code = null,
        public ?string $registered_via = null
    ) {
    }
}
