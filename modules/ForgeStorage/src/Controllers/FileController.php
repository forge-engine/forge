<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Controllers;

use App\Modules\ForgeStorage\Services\FileService;
use App\Modules\ForgeStorage\Services\StorageService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;
use Forge\Traits\ResponseHelper;

#[Service]
#[Middleware('web')]
final class FileController
{
    use ControllerHelper;
    use ResponseHelper;

    public function __construct(private StorageService $storageService, private FileService $fileService)
    {
    }

    #[Route(path: '/upload/{bucket}', method: "POST")]
    public function upload(Request $request, string $bucket): Response
    {
        if (!$request->hasFile('file')) {
            return new Response("No file upload", 400);
        }

        $file = $request->getFile('file');

        if (!$file) {
            return new Response("No file upload", 400);
        }

        $bucketdb = $this->storageService->findBucketByName($bucket);
        $path = $this->fileService->storeFile($file, $bucketdb->name);

        if (!$path) {
            return new Response("Failed to upload file", 500);
        }

        return new Response("File upload successfully");
    }

    #[Route("/file/{bucket}/{path:.+}", "GET")]
    public function getFile(string $bucket, string $path): Response
    {
        if ($this->storageService->exists($bucket, $path)) {
            $content = $this->storageService->get($bucket, $path);
            $mimeType = mime_content_type($this->storageService->getBucketPath($bucket) . '/' . $path);
            return new Response($content, 200, ['Content-Type' => $mimeType]);
        } else {
            return new Response("File not found", 404);
        }
    }

    #[Route("/file/url/{bucket}/{path:.+}", "GET")]
    public function getFileUrl(string $bucket, string $path): Response
    {
        $url = $this->storageService->getUrl($bucket, $path);
        return new Response("File URL: " . $url);
    }

    #[Route("/temporary-url/{bucket}/{id}/{path:.+}", "GET")]
    public function getTemporaryUrl(string $bucket, string $id, string $path): Response
    {
        $url = $this->storageService->temporaryUrl($bucket, $path, time() + 5, $id);
        return Redirect::to($url);
    }

    #[Route("/buckets", "GET")]
    public function listBuckets(): Response
    {
        $buckets = $this->storageService->listBuckets();
        return new Response("Buckets: " . implode(', ', $buckets));
    }

    #[Route("/buckets/db", "GET")]
    public function listBucketsFromDatabase(): Response
    {
        $buckets = $this->storageService->listBucketsFromDatabase();
        $bucketNames = array_map(fn ($bucket) => $bucket->name, $buckets);
        return new Response("Database Buckets: " . implode(', ', $bucketNames));
    }

    #[Route("/buckets/create/{name}", "POST")]
    public function createBucket(string $name): Response
    {
        if ($this->storageService->createBucket($name, ['public' => false])) {
            return new Response("Bucket '$name' created successfully!");
        } else {
            return new Response("Failed to create bucket '$name'", 500);
        }
    }

    #[Route("/storage/info/{id:\d+}", "GET")]
    public function getStorageInfo(string $id): Response
    {
        $storageInfo = $this->storageService->findStorageById($id);
        if ($storageInfo) {
            return new Response(
                "Storage Info: ID: " . $storageInfo->id . ", Path: " . $storageInfo->path . ", Size: " . $storageInfo->size
            );
        } else {
            return new Response("Storage info not found", 404);
        }
    }

    #[Route("/temporary-link/{cleanPath:.+}", "GET")]
    public function accessTemporaryLink(string $cleanPath): ?Response
    {
        $temporaryUrl = $this->storageService->findTemporaryUrlByCleanPath($cleanPath);
        $file = $this->storageService->findStorageById($temporaryUrl->storage_id);

        if ($temporaryUrl && $temporaryUrl->expires_at > date('Y-m-d H:i:s')) {
            return (new Response($this->storageService->get($temporaryUrl->bucket, $temporaryUrl->path), 200))->setHeader('Content-Type', $file->mime_type);
        }

        return new Response("Temporary link expired or invalid.", 404);
    }
}
