<?php
declare(strict_types=1);

namespace Forge\Core\Module\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ConfigDefaults
{
	public function __construct(
		public array $defaults
	){}
}