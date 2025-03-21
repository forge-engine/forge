<?php
declare(strict_types=1);

namespace Forge\Exceptions;

use Throwable;

class ResolveParameterException extends BaseException
{
	public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}