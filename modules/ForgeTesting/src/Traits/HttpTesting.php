<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting\Traits;

use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Router;

trait HttpTesting
{
    protected ?Router $router;

    protected function setHttpTestingRouter(Router $router): void
    {
        $this->router = $router;
    }

    protected function get(string $uri, array $queryParams = [], array $headers = []): Response
    {
        return $this->call('GET', $uri, [], $queryParams, $headers);
    }

    protected function post(string $uri, array $data = [], array $queryParams = [], array $headers = []): Response
    {
        return $this->call('POST', $uri, $data, $queryParams, $headers);
    }

    protected function put(string $uri, array $data = [], array $queryParams = [], array $headers = []): Response
    {
        return $this->call('PUT', $uri, $data, $queryParams, $headers);
    }

    protected function patch(string $uri, array $data = [], array $queryParams = [], array $headers = []): Response
    {
        return $this->call('PATCH', $uri, $data, $queryParams, $headers);
    }

    protected function delete(string $uri, array $data = [], array $queryParams = [], array $headers = []): Response
    {
        return $this->call('DELETE', $uri, $data, $queryParams, $headers);
    }

    public function call(string $method, string $uri, array $data = [], array $queryParams = [], array $headers = []): Response
    {
        $router = $this->router;

        // Prepare the server parameters for the request
        $serverParams = $_SERVER;
        $serverParams['REQUEST_METHOD'] = strtoupper($method);
        $serverParams['REQUEST_URI'] = $uri;
        $serverParams['PATH_INFO'] = parse_url($uri, PHP_URL_PATH);
        $serverParams['QUERY_STRING'] = http_build_query($queryParams);

        // Set the headers
        foreach ($headers as $key => $value) {
            $serverParams['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }

        // Create a Request object
        $request = new Request($queryParams, $data, $serverParams, strtoupper($method), $_COOKIE);

        // Dispatch the request through the router
        return $router->dispatch($request);
    }
}
