<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Dto;

use Forge\Core\Dto\Attributes\Sanitize;
use Forge\Core\Dto\BaseDto;
use Forge\Traits\DTOHelper;

#[Sanitize]
final class TemporaryUrlDto extends BaseDto
{
    use DTOHelper;

    public function __construct(
        public string $id,
        public string $clean_path,
        public string $bucket,
        public string $path,
        public string $token,
        public string $storage_id,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
        public ?\DateTimeImmutable $expires_at = null,
        public ?\DateTimeImmutable $delete_at = null
    ) {
    }
}
