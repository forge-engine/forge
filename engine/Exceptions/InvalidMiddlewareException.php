<?php
declare(strict_types=1);

namespace Forge\Exceptions;

final class InvalidMiddlewareException extends BaseException
{
	public function __construct(string $middlewareClass)
	{
		parent::__construct("Middleware class '$middlewareClass' must implement Middleware.");
	}
}