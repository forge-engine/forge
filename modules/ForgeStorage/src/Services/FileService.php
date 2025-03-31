<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Services;

use Forge\Core\Helpers\Strings;
use Forge\Core\Helpers\UUID;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\UploadedFile;

#[Service]
final class FileService
{
    public function __construct(
        private StorageService $storageService,
        private Config $config
    ) {
    }

    public function sanitizeFilename(string $filename): string
    {
        $filename = Strings::toKebabCase(pathinfo($filename, PATHINFO_FILENAME));
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return $filename . ($extension ? "." . $extension : "");
    }

    public function generateFilename(string $originalName): string
    {
        $hashFileNames = true;
        if ($hashFileNames) {
            return UUID::generate();
        }
        return $this->sanitizeFilename($originalName);
    }

    public function storeFile(UploadedFile $file, string $bucketName): string|false
    {
        $bucket = $this->findBucketByName($bucketName);
        if (!$bucket) {
            return false;
        }

        $filename = $this->generateFilename($file->getClientFilename());
        $path = "user-files/" . $filename;

        if ($this->storageService->put($bucket->name, $path, stream_get_contents($file->getStream()))) {
            $this->createStorageRecord($bucket->id, $bucket->name, $path, $file->getSize(), $file->getClientMediaType());
            return $path;
        }
        return false;
    }

    public function resizeImage(string $path, int $width, int $height, int $quality = 80): bool
    {
        $imageType = mime_content_type($path);
        if (!in_array($imageType, ['image/jpeg', 'image/png', 'image/webp'])) {
            return false;
        }

        switch ($imageType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($path);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($path);
                break;
            default:
                return false;
        }

        $resized = imagescale($image, $width, $height);
        if (!$resized) {
            return false;
        }

        switch ($imageType) {
            case 'image/jpeg':
                imagejpeg($resized, $path, $quality);
                break;
            case 'image/png':
                imagepng($resized, $path, (int)round($quality / 10));
                break;
            case 'image/webp':
                imagewebp($resized, $path, $quality);
                break;
        }

        imagedestroy($image);
        imagedestroy($resized);

        return true;
    }

    private function findBucketByName(string $name)
    {
        return $this->storageService->findBucketByName($name);
    }

    private function createStorageRecord(string $bucketId, string $bucketName, string $path, int $size, string $mime): void
    {
        $this->storageService->createStorageRecord(
            bucketId: $bucketId,
            bucketName: $bucketName,
            path: $path,
            size: $size,
            mimeType: $mime
        );
    }
}
