<?php
declare(strict_types=1);

namespace Forge\Exceptions;

final class MissingServiceException extends BaseException
{
	public function __construct(string $serviceName)
	{
		parent::__construct("Service '$serviceName' not found in the container");
	}
}