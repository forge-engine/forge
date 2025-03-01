<?php

namespace MyApp\Controllers;

use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Debug;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Core\Contracts\Modules\ViewEngineInterface;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;

class FileController
{
    /**
     * @inject
     */
    private DatabaseInterface $db;

    public function serveFile(Request $request): Response
    {
        //Debug::dd($request);
        $path = $request->getAttribute('path');
        $bucket = $request->getAttribute('bucket');

        if (!$path) {
            return (new Response())->html('File not found')->setStatusCode(404);
        }

        $storage = App::storage();
        if ($storage->exists($bucket, $path)) {
            $content = $storage->get($bucket, $path);
            return (new Response())->setHeader('Content-Type', 'application/octet-stream')->setContent($content)->setStatusCode(200);
        }

        return new Response('File not found.', 404);
    }
}
