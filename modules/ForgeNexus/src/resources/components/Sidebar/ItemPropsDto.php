<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Resources\Components\Sidebar;

class ItemPropsDto
{
    public function __construct(
        public bool $isActive = false,
        public string $target = '',
        public string $label = '',
        public ?string $icon = null,
    ) {
    }
}
