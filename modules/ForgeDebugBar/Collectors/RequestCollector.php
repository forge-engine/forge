<?php

namespace Forge\Modules\ForgeDebugbar\Collectors;

use Forge\Http\Request;

class RequestCollector implements CollectorInterface
{
    public static function collect(...$args): array
    {
        /** @var Request $request */
        $request = $args[0];

        if (!$request) {
            return ['error' => 'Request object not available'];
        }

        return [
            'url' => $request->fullUrl(),
            'method' => $request->getMethod(),
            'ip' => $request->ip(),
            'headers' => $request->getHeaders(),
            'query' => $request->query()->all(),
            'body' => $request->request()->all(),
            'cookies' => $_COOKIE,
            'files' => $_FILES,
        ];
    }
}