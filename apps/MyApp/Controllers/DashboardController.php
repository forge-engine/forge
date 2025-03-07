<?php

namespace MyApp\Controllers;

use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Date;
use Forge\Core\Helpers\Debug;
use Forge\Core\Helpers\UUID;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Core\Contracts\Modules\ViewEngineInterface;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeExplicitOrm\Exception\RepositoryException;
use Forge\Modules\ForgeOrm\QueryBuilder;
use MyApp\DataTransferObjects\CategoryDTO;
use MyApp\Repositories\CategoryRepository;

class DashboardController
{
    /**
     * @inject
     */
    private ViewEngineInterface $view;

    /**
     * @inject
     */
    private DatabaseInterface $db;

    /**
     * @inject
     */
    private QueryBuilder $queryBuilder;

    /**
     * @inject
     */
    private CategoryRepository $categoryRepository;

    /**
     * @throws RepositoryException
     */
    public function index(Request $request): Response
    {
        $categoryId = 1;

        /** @var CategoryDTO $categoryDto */
        $categoryDto = $this->categoryRepository->find($categoryId);
        $allCategories = $this->categoryRepository->findAll();
        $categoryBySlug = $this->categoryRepository->findBySlug('getting-starter');

        $sections = null;
        if ($categoryDto) {
            $sectionsRelation = $this->categoryRepository->sections();
            $sections = $sectionsRelation->for($categoryDto);
        }

        // Using Query Builder
        $filesRaw = $this->queryBuilder->table('storage')->get();
        $bucketsRaw = $this->queryBuilder->table('buckets')->get();

        // Low level database access
        $files = $this->db->query("SELECT * FROM storage");
        $buckets = $this->db->query("SELECT * FROM buckets");
        $data = [
            'files' => $files,
            'buckets' => $buckets,
            'filesRaw' => $filesRaw,
            'bucketRaw' => $bucketsRaw,
            'category' => $categoryDto,
            'allCategories' => $allCategories,
            'categoryBySlug' => $categoryBySlug,
            'sections' => $sections
        ];
        return $this->view->render('storage.dashboard', $data, 'base');
    }

    public function createBucket(Request $request): Response
    {
        $bucketName = $request->getData('bucket_name');
        $storage = App::storage();

        $existingBucket = $this->db->table('buckets')->where('name', $bucketName)->first();
        if ($existingBucket) {
            return (new Response())->html('Bucket already exists')->setStatusCode(400);
        }

        if ($storage->createBucket($bucketName)) {
            $bucketId = UUID::generate();
            $this->db->table('buckets')->insert([
                'id' => $bucketId,
                'name' => $bucketName,
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ]);

            return (new Response())
                ->html('Bucket created successfully')
                ->setStatusCode(200);
        }

        return (new Response())
            ->html('Failed to create bucket.')
            ->setStatusCode(500);
    }

    public function getUrl(Request $request): Response
    {
        $bucket = $request->getQuery('bucket');
        $path = $request->getQuery('path');
        $storage = App::storage();

        $url = $storage->getUrl($bucket, $path);
        return (new Response())
            ->html("URL: <a href='{$url}'>{$url}</a>");
    }

    public function getTemporaryUrl(Request $request): Response
    {
        $bucket = $request->getQuery('bucket');
        $path = $request->getQuery('path');
        $expires = time() + 3600;
        $storage = App::storage();

        $url = $storage->temporaryUrl($bucket, $path, $expires);
        return (new Response())
            ->html("Temporary URL: <a href='{$url}'>{$url}</a>");
    }

    public function handleUpload(Request $request): Response
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file !== null) {
                $storage = App::storage();
                $bucketName = $request->getData('bucket_name');

                $bucket = $this->db->table('buckets')->where('name', $bucketName)->first();

                if (!$bucket) {
                    return (new Response())->html('Bucket does not exist')->setStatusCode(400);
                }


                $path = 'images/' . bin2hex(random_bytes(16)) . '-' . basename($file['name']);
                $size = $file['size'];
                $mimeType = mime_content_type($file['tmp_name']);


                if ($storage->put($bucket['name'], $path, file_get_contents($file['tmp_name']))) {
                    $this->db->table('storage')->insert([
                        'id' => UUID::generate(),
                        'bucket_id' => $bucket['id'],
                        'bucket' => $bucket['name'],
                        'path' => $path,
                        'size' => $size,
                        'mime_type' => $mimeType,
                        'created_at' => Date::now(),
                        'updated_at' => Date::now(),
                    ]);

                    return (new Response())->html('File uploaded successfully!');
                }
            }
        }

        return (new Response())->html('Failed to upload file.');
    }
}
