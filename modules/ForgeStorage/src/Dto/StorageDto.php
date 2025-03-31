<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Dto;

use Forge\Core\Dto\Attributes\Sanitize;
use Forge\Core\Dto\BaseDto;
use Forge\Core\Helpers\Format;
use Forge\Traits\DTOHelper;

#[Sanitize]
final class StorageDto extends BaseDto
{
    use DTOHelper;

    public function __construct(
        public string $id,
        public string $bucket_id,
        public string $bucket,
        public string $path,
        public int $size,
        public string $mime_type,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
        public ?\DateTimeImmutable $expires_at = null,
    ) {
    }

    public function formatSize(): string
    {
        return Format::fileSize($this->size);
    }
}
