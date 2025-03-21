<?php
declare(strict_types=1);

namespace Forge\Core\Module\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class CLICommand
{
	public function __construct(
		public string $name,
		public string $description
	){}
}