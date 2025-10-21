<?php

declare(strict_types=1);

namespace App\Modules\ForgeUi\Resources\Components\Ui;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: "ForgeUi:Ui/Alert", useDto: true)]
final class Alert extends BaseComponent
{
    public function render(): string
    {
        return $this->renderview(viewPath: "ui/alert", data: $this->props, loadFromModule: true);
    }
}
