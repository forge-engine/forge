<?php

declare(strict_types=1);

namespace App\Modules\ForgeWelcome\Resources\Components\Footer;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: "forge-welcome:footer", useDto: false)]
class Footer extends BaseComponent
{
    public function render(): mixed
    {
        return $this->renderview(viewPath: "Footer/FooterView", data: [], loadFromModule: true);
    }
}
