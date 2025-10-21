<?php

declare(strict_types=1);

namespace App\Components\Ui;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: 'Ui/Alert', useDto: true)]
final class Alert extends BaseComponent
{
    public function render(): string
    {
        return $this->renderview('ui/alert', $this->props);
    }
}