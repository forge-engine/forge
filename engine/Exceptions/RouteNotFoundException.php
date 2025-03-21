<?php
declare(strict_types=1);

namespace Forge\Exceptions;

final class RouteNotFoundException extends BaseException
{
	public function __construct(string $method = '', string $path = '')
	{
		parent::__construct("Route not found: $method $path");	
	}
}