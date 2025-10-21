<?php

declare(strict_types=1);

namespace App\Components\Ui;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: "Ui/Footer")]
class Footer extends BaseComponent
{
    public function render(): string
    {
        return $this->renderview("ui/footer");
    }
}
