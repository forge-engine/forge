<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting\Traits;

use Forge\Core\DI\Container;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Router;
use Forge\Core\Services\TokenManager;

trait HttpTesting
{
    protected static ?\Forge\Core\Http\Kernel $kernel = null;
    private ?string $csrfToken = null;

    protected function loadCsrfToken(): void
    {
        /** @var TokenManager $tm */
        $tm = Container::getInstance()->get(TokenManager::class);
        $this->csrfToken = $tm->getToken("web");
    }

    protected function withCsrf(array $data = []): array
    {
        if ($this->csrfToken === null) {
            $this->loadCsrfToken();
        }
        $data["_token"] = $this->csrfToken;
        return $data;
    }

    protected function csrfHeaders(): array
    {
        if ($this->csrfToken === null) {
            $this->loadCsrfToken();
        }
        return ["X-CSRF-TOKEN" => $this->csrfToken];
    }

    protected function get(string $uri, array $headers = []): Response
    {
        return $this->sendRequest("GET", $uri, [], $headers);
    }

    protected function post(
        string $uri,
        array $data = [],
        array $headers = [],
    ): Response {
        return $this->sendRequest("POST", $uri, $data, $headers);
    }

    protected function patch(
        string $uri,
        array $data = [],
        array $headers = [],
    ): Response {
        return $this->sendRequest("PATCH", $uri, $data, $headers);
    }

    private function sendRequest(
        string $method,
        string $uri,
        array $body = [],
        array $headers = [],
    ): Response {
        $query = [];
        $uriParts = parse_url($uri);
        $path = $uriParts["path"] ?? "/";
        if (isset($uriParts["query"])) {
            parse_str($uriParts["query"], $query);
        }

        $server = [
            "REQUEST_METHOD" => $method,
            "REQUEST_URI" => $uri,
            "SERVER_NAME" => "localhost",
            "SERVER_PORT" => "80",
            "PATH_INFO" => $path,
        ];

        foreach ($headers as $key => $value) {
            $headerKey = "HTTP_" . strtoupper(str_replace("-", "_", $key));
            $server[$headerKey] = $value;
        }

        $request = new Request(
            queryParams: $query,
            postData: $body,
            serverParams: $server,
            requestMethod: $method,
            cookies: [],
            query: $uriParts["query"] ?? null,
        );

        return Router::init(Container::getInstance())->dispatch($request);
    }
}
