<?php

/**
 * Middleware Configuration
 *
 * This file controls which middlewares are applied to your routes.
 *
 * HOW IT WORKS:
 * - If you explicitly list engine middlewares below, their order is respected
 * - If you omit engine middlewares, they are auto-discovered and merged (backward compatible)
 * - To remove an engine middleware, simply don't include it in the list
 * - To reorder middlewares, change their position in the array
 *
 * AVAILABLE ENGINE MIDDLEWARES:
 *
 * Global Group (applies to all routes):
 * - \Forge\Core\Http\Middlewares\RateLimitMiddleware::class (order: 0) - Rate limiting
 * - \Forge\Core\Http\Middlewares\CircuitBreakerMiddleware::class (order: 1) - Circuit breaker
 * - \Forge\Core\Http\Middlewares\CorsMiddleware::class (order: 2) - CORS headers
 * - \Forge\Core\Http\Middlewares\SanitizeInputMiddleware::class (order: 3, disabled by default) - Input sanitization
 * - \Forge\Core\Http\Middlewares\CompressionMiddleware::class (order: 4) - Response compression
 *
 * Web Group (applies to web routes):
 * - \Forge\Core\Http\Middlewares\SessionMiddleware::class (order: 0) - Session management
 * - \Forge\Core\Http\Middlewares\CsrfMiddleware::class (order: 1) - CSRF protection
 * - \Forge\Core\Http\Middlewares\RelaxSecurityHeadersMiddleware::class (order: 3) - Security headers
 *
 * API Group (applies to API routes):
 * - \Forge\Core\Http\Middlewares\IpWhiteListMiddleware::class (order: 0) - IP whitelist
 * - \Forge\Core\Http\Middlewares\ApiKeyMiddleware::class (order: 1) - API key auth
 * - \Forge\Core\Http\Middlewares\CookieMiddleware::class (order: 2) - Cookie handling
 * - \Forge\Core\Http\Middlewares\ApiMiddleware::class (order: 2) - API response formatting
 *
 * EXAMPLE: Explicitly control engine middleware order
 * 'global' => [
 *     \Forge\Core\Http\Middlewares\RateLimitMiddleware::class,
 *     \Forge\Core\Http\Middlewares\CircuitBreakerMiddleware::class,
 *     // Omit SanitizeInputMiddleware to remove it
 *     \Forge\Core\Http\Middlewares\CompressionMiddleware::class,
 *     // Your custom middleware
 *     \App\Middlewares\CustomMiddleware::class,
 * ],
 */

return [
    "global" => [
        \Forge\Core\Http\Middlewares\RateLimitMiddleware::class,
        \Forge\Core\Http\Middlewares\CircuitBreakerMiddleware::class,
        \Forge\Core\Http\Middlewares\CorsMiddleware::class,
        \Forge\Core\Http\Middlewares\SanitizeInputMiddleware::class,
        \Forge\Core\Http\Middlewares\CompressionMiddleware::class,
    ],
    "web" => [
        \Forge\Core\Http\Middlewares\SessionMiddleware::class,
        \Forge\Core\Http\Middlewares\CsrfMiddleware::class,
        \Forge\Core\Http\Middlewares\RelaxSecurityHeadersMiddleware::class,
        \App\Modules\ForgeWire\Middlewares\ForgeWireMiddleware::class,
    ],
    "api" => [
        \Forge\Core\Http\Middlewares\IpWhiteListMiddleware::class,

        \Forge\Core\Http\Middlewares\CookieMiddleware::class,
        \Forge\Core\Http\Middlewares\ApiMiddleware::class,
    ],
    "api-auth" => [],
    "auth" => [\App\Modules\ForgeAuth\Middlewares\AuthMiddleware::class],
    "hub-permissions" => [
        App\Modules\ForgeHub\Middlewares\HubPermissionMiddleware::class,
    ],
];
