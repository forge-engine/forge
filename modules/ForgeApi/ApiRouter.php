<?php

namespace Forge\Modules\ForgeApi;

use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Modules\Router\BasicRouter;

class ApiRouter extends BasicRouter
{
    public function handleRequest(Request $request): Response
    {
        $response = parent::handleRequest($request);
        if (!$response->getHeader('Content-Type')) {
            $response->setHeader('Content-Type', 'application/json');
        }

        return $response;
    }

    public function jsonRoute(string $method, string $uri, callable $handler): void
    {

        $this->addRoute($method, $uri, function (Request $request) use ($handler) {
            print_r('aver');
            $data = $handler($request);

            //return (new ApiResponse())->json($data);
        });
    }

    public function apiResource(string $uri, string $controller): void
    {
        $this->addRoute('GET', $uri, [$controller, 'index']);
        $this->addRoute('POST', $uri, [$controller, 'store']);
        $this->addRoute('GET', $uri . '/{id}', [$controller, 'show']);
        $this->addRoute('PUT', $uri . '/{id}', [$controller, 'update']);
        $this->addRoute('DELETE', $uri . '/{id}', [$controller, 'destroy']);
    }
}