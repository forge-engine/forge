<?php

declare(strict_types=1);

namespace App\View\Components\Footer;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component("footer")]
class Footer extends BaseComponent
{
    public function __construct(array $props = [])
    {
        parent::__construct($props);
    }

    public function render(): mixed
    {
        $data = ["props" => $this->props];
        return $this->renderview("Footer/FooterView", $data);
    }
}
