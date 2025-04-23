<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Resources\Components\Sidebar;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name:'nexus:sidebar:item', useDto: true)]
class Item extends BaseComponent
{
    public function __construct(ItemPropsDto $props)
    {
        parent::__construct($props);
    }
    public function render(): mixed
    {
        return $this->renderview(viewPath:'Sidebar/views/sidebar-item', loadFromModule: true, data: $this->props);
    }
}
