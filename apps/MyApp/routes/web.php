<?php

use Forge\Core\Helpers\App;
use MyApp\Controllers\DashboardController;
use MyApp\Controllers\FileController;
use MyApp\Controllers\HomeController;
use MyApp\Controllers\DocController;
use MyApp\Controllers\UserController;
use MyApp\Middleware\AdminAuthMiddleware;
use Forge\Core\Contracts\Modules\RouterInterface;
use MyApp\Middleware\RequestLoggingMiddleware;
use MyApp\Middleware\FileExpirationMiddleware;

$router = App::router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/docs', [DocController::class, 'index']);
$router->get('/contact', function () {
    return (new \Forge\Http\Response())->html('Contat form');
});

$router->get('/favicon.ico', function () {
    $faviconPath = BASE_PATH . '/public/favicon.ico';
    if (file_exists($faviconPath)) {
        $content = file_get_contents($faviconPath);
        $contentType = ['Content-Type' => 'image/x-icon'];
        $statusCode = 200;
        return (new \Forge\Http\Response())->setContent($content)->setHeader($contentType)->setStatusCode($statusCode);
    }
    return new Response('', 404);
});

$router->get('/files/{clean_path}', [FileController::class, 'serveFile'], [FileExpirationMiddleware::class]);


$router->resource('/users', UserController::class);

$router->group('/admin', function (RouterInterface $router) {
    $router->middleware([RequestLoggingMiddleware::class, AdminAuthMiddleware::class]);

    $router->get('/', function () {
        return (new \Forge\Http\Response())->html('Welcome Admin');
    });

    $router->get('/dashboard', function () {
        return (new \Forge\Http\Response())->html('Welcome to the dashboard');
    });
});

$router->group('/dashboard', function (RouterInterface $router) {
    $router->get('/', [DashboardController::class, 'index']);
    $router->post('/create-bucket', [DashboardController::class, 'createBucket']);
    $router->get('/url', [DashboardController::class, 'getUrl']);
    $router->get('/temporary-url', [DashboardController::class, 'getTemporaryUrl']);
    $router->post('/upload', [DashboardController::class, 'handleUpload']);
});