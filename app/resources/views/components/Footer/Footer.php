<?php

declare(strict_types=1);

namespace App\View\Components\Footer;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: "footer")]
class Footer extends BaseComponent
{
    public function render(): mixed
    {
        return $this->renderview("Footer/FooterView");
    }
}
