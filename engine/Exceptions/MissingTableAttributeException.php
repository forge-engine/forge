<?php
declare(strict_types=1);

namespace Forge\Exceptions;

final class MissingTableAttributeException extends BaseException
{
	public function __construct()
	{
		parent::__construct("Model missing #[Table] attribute");
	}
}