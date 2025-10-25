<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Dto;

use Forge\Core\Dto\BaseDto;

/**
 * @property ?int $referal_code
 * @property ?string $registered_via
 * @property ?array $notifications
 */
final class UserMetadataDto extends BaseDto
{
    public function __construct(
        public ?int    $referal_code = null,
        public ?string $registered_via = null,
        public ?array  $notifications = null,
    )
    {
    }
}