<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Resources\Components\Sidebar;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name:'nexus:sidebar', useDto: false)]
class Sidebar extends BaseComponent
{
    public function render(): mixed
    {
        return $this->renderview(viewPath:'Sidebar/SidebarView', loadFromModule: true);
    }
}
