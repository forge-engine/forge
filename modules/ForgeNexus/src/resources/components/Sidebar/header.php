<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Resources\Components\Sidebar;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name:'nexus:sidebar:header', useDto: false)]
class Header extends BaseComponent
{
    public function __construct(array $props)
    {
        parent::__construct($props);
    }
    public function render(): mixed
    {
        return $this->renderview(
            viewPath:'Sidebar/views/header',
            loadFromModule: true,
            data: $this->props
        );
    }
}
