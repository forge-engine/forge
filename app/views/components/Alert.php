<?php
declare(strict_types=1);

namespace App\View\Components;

use Forge\Core\View\Component;

#[Component('alert')]
class Alert
{
    private array $props;

    public function __construct(array $props = [])
    {
        $this->props = $props;
    }

    public function render(): void
    {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<strong>" . ($this->props['type'] ?? 'Alert') . ":</strong> ";
        echo "{$this->props['children']}";
        echo "</div>";
    }
}