<?php

declare(strict_types=1);

namespace App\View\Components;

use Forge\Core\View\Component;

#[Component('footer')]
class Footer
{
    public function __construct(
        private array $props = []
    )
    {
        $this->props = $this->props;
    }

    public function render(): void
    {
        echo "<footer><p>&copy; {$this->props['year']} Forge engine</p></footer>";
    }
}