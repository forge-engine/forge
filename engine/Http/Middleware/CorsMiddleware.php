<?php

namespace Forge\Http\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Core\Configuration\Config;
use Forge\Core\DependencyInjection\Container;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;
use Exception;
use Forge\Http\Session;

class CorsMiddleware extends MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;

    /**
     * @param array<int,mixed> $allowedOrigins
     * @param array<int,mixed> $allowedMethods
     * @param array<int,mixed> $allowedHeaders
     */
    public function __construct(
        Container $container,
        Session   $session,
        array     $config = []
    )
    {
        parent::__construct($container, $session, $config);

        $corsConfig = $container->get(Config::class)->get('app.cors', []);
        $this->allowedOrigins = $config['allowed_origins'] ?? $corsConfig['allowed_origins'] ?? ['*'];
        $this->allowedMethods = $config['allowed_methods'] ?? $corsConfig['allowed_methods'] ?? ['GET', 'POST'];
        $this->allowedHeaders = $config['allowed_headers'] ?? $corsConfig['allowed_headers'] ?? ['Content-Type'];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->getHeader('Origin');

        if (in_array($origin, $this->allowedOrigins) || $this->allowedOrigins === ['*']) {

            $response = $next($request);

            if (!$response instanceof Response) {
                throw new Exception('Middleware did not return a Response object.');
            }

            $response->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
            $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            return $response;
        }

        return (new Response())->setStatusCode(403)->text('CORS not allowed');
    }
}
