<?php

namespace MyApp\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Core\Helpers\Debug;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;
use Exception;
use Forge\Core\Helpers\App;

class FileExpirationMiddleware extends MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $cleanPath = $request->getAttribute('clean_path');
        $token = $request->getQuery('token');
        $expires = $request->getQuery('expires');

        $record = App::db()->table('temporary_urls')
            ->where('clean_path', $cleanPath)
            ->where('token', $token)
            ->first();

        if (!$record) {
            return (new Response())
                ->html('File not found')
                ->setStatusCode(404);
        }


        if ($record['expires_at'] && strtotime($record['expires_at']) < time()) {
            return (new Response())
                ->html('Link expired')
                ->setStatusCode(403);
        }

        $expectedToken = hash_hmac('sha256', "{$record['bucket']}/{$record['path']}|{$expires}", App::config()->get('app.key'));

        if (!hash_equals($expectedToken, $token)) {
            return (new Response())
                ->html('Invalid token')
                ->setStatusCode(403);
        }

        $request->setAttribute('bucket', $record['bucket']);
        $request->setAttribute('path', $record['path']);

        return $next($request);
    }
}
