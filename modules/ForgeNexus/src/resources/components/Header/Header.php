<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Resources\Components\Header;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component('nexus:header', useDto: false)]
final class Header extends BaseComponent
{
    public function render(): mixed
    {
        return $this->renderview(viewPath: 'Header/HeaderView', loadFromModule: true);
    }
}
