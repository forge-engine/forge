<?php
declare(strict_types=1);

namespace Forge\Core\Module\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Compatibility
{
	public function __construct(
		public ?string $framework = null,
		public ?string $php = null
	){}
}