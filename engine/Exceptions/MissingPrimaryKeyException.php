<?php
declare(strict_types=1);

namespace Forge\Exceptions;

final class MissingPrimaryKeyException extends BaseException
{
	public function __construct()
	{
		parent::__construct("No primary key defined");
	}
}