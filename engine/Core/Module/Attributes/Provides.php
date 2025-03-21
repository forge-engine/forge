<?php
declare(strict_types=1);

namespace Forge\Core\Module\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Provides
{
	public function __construct(
		public string $interface,
		public string $version	
	){}
}