<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Dto;

use Forge\Core\Dto\Attributes\Sanitize;
use Forge\Core\Dto\BaseDto;
use Forge\Traits\DTOHelper;

#[Sanitize]
final class BucketDto extends BaseDto
{
    use DTOHelper;

    public function __construct(
        public string $id,
        public string $name,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
    ) {
    }
}
