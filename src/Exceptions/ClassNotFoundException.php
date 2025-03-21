<?php
declare(strict_types=1);

namespace Forge\Exceptions;

class ClassNotFoundException extends BaseException
{
	public function __construct(string $className)
	{
		parent::__construct("Class {$className} not found");
	}
}