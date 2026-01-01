<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Dto;

use Forge\Core\Dto\BaseDto;

final class UserNotificationDto extends BaseDto
{
    public function __construct(
        public ?bool $email = false,
        public ?bool $mentions = false,
    ) {
        //
    }
}
