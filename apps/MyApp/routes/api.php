<?php

use Forge\Core\Helpers\App;
use Forge\Core\Contracts\Modules\RouterInterface;
use Forge\Http\Response;

$router = App::router();

$router->group('/api/v1', function (RouterInterface $router) {
    $router->middleware([]);

    $router->get('/', function () {
        $response = new Response();
        $response->setStatusCode(500);
        return $response->text('Not allowed');
    });

    $router->get('/status', function () {
        return (new Response())->json(['status' => 'ok']);
    });
});