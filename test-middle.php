<?php

return [
	'global' => [
		\Forge\Core\Http\Middlewares\RateLimitMiddleware::class,
		\Forge\Core\Http\Middlewares\CircuitBreakerMiddleware::class,
		\Forge\Core\Http\Middlewares\CorsMiddleware::class,
		\Forge\Core\Http\Middlewares\SanitizeInputMiddleware::class,
		\Forge\Core\Http\Middlewares\CompressionMiddleware::class,
	],
	'web' => [
		//\Forge\Core\Http\Middlewares\RelaxSecurityHeadersMiddleware::class,
		\Forge\Core\Http\Middlewares\SessionMiddleware::class,
		\Forge\Core\Http\Middlewares\CookieMiddleware::class,
	],
	'api' => [
		\App\Modules\Core\Middlewares\EnvironmentMiddleware::class,
		\App\Modules\Core\Middlewares\AppRateLimitMiddleware::class,
		\App\Modules\Core\Middlewares\HandShakeMiddleware::class,
		\App\Modules\Core\Middlewares\DeviceProvisionMiddleware::class,
		\App\Modules\Core\Middlewares\FraudDetectionMiddleware::class,
		\App\Modules\Core\Middlewares\GeoMiddleware::class,
		\App\Modules\Core\Middlewares\AppIntegrityMiddleware::class,
		\App\Modules\Core\Middlewares\TimeDiffMiddleware::class,
		\Forge\Core\Http\Middlewares\IpWhiteListMiddleware::class,
		\Forge\Core\Http\Middlewares\ApiKeyMiddleware::class,
		\Forge\Core\Http\Middlewares\ApiMiddleware::class,
	]
];
