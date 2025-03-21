<?php
declare(strict_types=1);

namespace Forge\Core\Module\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Module
{
	public function __construct(
		public ?string $name = null,
		public ?string $version = null,
		public ?string $description = null,
		public int $order = PHP_INT_MAX,
		public bool $core = false	
	){}
}