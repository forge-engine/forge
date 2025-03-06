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
$router->get('/login', [\MyApp\Controllers\AuthController::class, 'loginForm']);
$router->post('/login', [\MyApp\Controllers\AuthController::class, 'login']);
$router->get('/register', [\MyApp\Controllers\AuthController::class, 'registerForm']);
$router->post('/register', [\MyApp\Controllers\AuthController::class, 'register']);
$router->get('/docs/{category}/{slug}', [DocController::class, 'index']);
$router->get('/contact', function () {
    return (new \Forge\Http\Response())->html('Contat form');
});

$router->resource('/flash-message-test', \MyApp\Controllers\FlashMessageController::class);


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
    $router->middleware([\Forge\Modules\ForgeAuth\Middleware\AuthMiddleware::class]);
    $router->get('/', [DashboardController::class, 'index']);
    $router->post('/create-bucket', [DashboardController::class, 'createBucket']);
    $router->get('/url', [DashboardController::class, 'getUrl']);
    $router->get('/temporary-url', [DashboardController::class, 'getTemporaryUrl']);
    $router->post('/upload', [DashboardController::class, 'handleUpload']);
});