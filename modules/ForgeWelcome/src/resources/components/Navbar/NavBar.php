<?php

declare(strict_types=1);

namespace App\Modules\ForgeWelcome\Resources\Components\Navbar;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: "forge-welcome:navbar", useDto: false)]
class NavBar extends BaseComponent
{
    public function render(): mixed
    {
        return $this->renderview(viewPath: "NavBar/NavbarView", data: [], loadFromModule: true);
    }
}
