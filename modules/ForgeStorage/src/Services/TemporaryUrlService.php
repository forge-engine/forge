<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Services;

use App\Modules\ForgeStorage\Dto\TemporaryUrlDto;
use App\Modules\ForgeStorage\Repositories\TemporaryUrlRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\UUID;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;

#[Service]
#[Provides]
#[Requires]
final class TemporaryUrlService
{
    public function __construct(
        private TemporaryUrlRepository $temporaryUrlRepository,
    ) {
    }

    public function findTemporaryUrlByCleanPath(string $cleanPath): ?TemporaryUrlDto
    {
        return $this->temporaryUrlRepository->findByCleanPath($cleanPath);
    }

    public function createTemporaryUrl(string $bucket, string $path, int $expires, string $file): string
    {
        $token = hash_hmac('sha256', "{$bucket}/{$path}|{$expires}", $_ENV['APP_KEY']);
        $cleanPath = bin2hex(random_bytes(16)) . '-' . basename($path);
        $expiresAt = date('Y-m-d H:i:s', $expires);

        $this->temporaryUrlRepository->create([
            'id' => UUID::generate(),
            'clean_path' => $cleanPath,
            'bucket' => $bucket,
            'path' => $this->normalizePath($path),
            'expires_at' => $expiresAt,
            'token' => $token,
            'storage_id' => $file,
        ]);

        return "/temporary-link/{$cleanPath}";
    }

    public function deleteExpiredTemporaryUrls(): int
    {
        if (method_exists($this->temporaryUrlRepository, 'deleteExpired')) {
            //return $this->temporaryUrlRepository->deleteExpired();
        }
        return 0;
    }

    private function normalizePath(string $path)
    {
        $lastSlashPosition = strrpos($path, '/');
        return  substr($path, 0, $lastSlashPosition);
    }
}
