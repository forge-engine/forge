<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Controllers;

use App\Modules\ForgeStorage\Services\StorageService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
#[Middleware('App\Modules\ForgeAuth\Middlewares\AuthMiddleware')]
final class FileManagerController
{
    use ControllerHelper;

    public function __construct(private StorageService $storageService)
    {
    }

    #[Route('/admin/buckets', 'GET')]
    public function bucketsIndex(): Response
    {
        $bucketsFromDatabase = $this->storageService->listBucketsFromDatabase();

        $data = [
            'title' => 'Buckets',
            'buckets' => $bucketsFromDatabase,
        ];

        return $this->view('admin/bucket-list', $data);
    }

    #[Route('/admin/bucket-files/{bucket}', 'GET')]
    public function index(string $bucket): Response
    {
        $storageRecords = $this->storageService->findStorageRecordsByBucket($bucket);
        $bucketInfo = $this->storageService->findBucketByName($bucket);

        $data = [
            'title' => "Bucket $bucketInfo->name",
            'storageRecords' => $storageRecords,
            'bucket' => $bucketInfo,
            'totalSize' => 0
        ];

        return $this->view('admin/bucket-file-list', $data);
    }

    #[Route('/admin/buckets/create', 'POST')]
    public function createBucket(Request $request): Response
    {
        $bucketName = $request->postData['name'] ?? '';

        if ($bucketName) {
            if ($this->storageService->createBucket($bucketName, ['public' => false])) {
                $data = ['message' => "Bucket '$bucketName' created successfully!"];
                return $this->view("storage/bucket-created-success", $data);
            } else {
                $data = ['error' => "Failed to create bucket '$bucketName'"];
                return $this->view("storage/bucket-created-failure", $data, 500);
            }
        } else {
            return new Response("Bucket name cannot be empty.", 400);
        }
    }

    #[Route('/admin/buckets/list', 'GET')]
    public function listBuckets(): Response
    {
        $buckets = $this->storageService->listBucketsFromDatabase();
        return $this->view("admin/bucket-list-only", ['buckets' => $buckets]);
    }

    #[Route('/admin/files/list/{bucket}', 'GET')]
    public function listFiles(?string $bucket = null): Response
    {
        $storageRecords = $this->storageService->findStorageRecordsByBucket($bucket);
        return $this->view("admin/file-list-table", ['storageRecords' => $storageRecords, 'currentBucket' => $bucket]);
    }

    #[Route('/admin/buckets/list/db', 'GET')]
    public function listDatabaseBuckets(): Response
    {
        $buckets = $this->storageService->listBucketsFromDatabase();
        return $this->view("storage/bucket-list-Database", ['buckets' => $buckets]);
    }

    #[Route('/admin/buckets/list/fs', 'GET')]
    public function listFileSystemBuckets(): Response
    {
        $buckets = $this->storageService->listBuckets();
        return $this->view("storage/bucket-list-filesystem", ['buckets' => $buckets]);
    }

    // Placeholder for deleting a bucket
    #[Route('/admin/buckets/delete/{name}', 'DELETE')]
    public function deleteBucket(string $name): Response
    {
        // Logic to delete the bucket
        // For now, let's just return a success message
        return new Response("<p>Bucket '$name' deleted successfully.</p>");
    }

    // Placeholder for deleting a file
    #[Route('/admin/files/delete/{bucket}/{path:.+}', 'DELETE')]
    public function deleteFile(string $bucket, string $path): Response
    {
        // Logic to delete the file
        // For now, let's just return a success message
        return new Response("<p>File '$path' in bucket '$bucket' deleted successfully.</p>");
    }
}
