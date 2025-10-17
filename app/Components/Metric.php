<?php

namespace App\Components;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Core\WireComponent;
use Forge\Core\View\Component;

final class Metric extends WireComponent
{
    #[State]
    public array $results = [];

    public function mount(array $props = []): void
    {
        $this->results = [
            "plain" => null,
            "forge_component" => null,
            "wire_component" => null,
            "mixed" => null,
        ];
    }

    #[Action]
    public function run(string $type): void
    {
        $methods = [
            "plain" => "renderPlain",
            "forge_component" => "renderForgeComponent",
            "wire_component" => "renderWireComponent",
            "mixed" => "renderMixed",
        ];

        if (!isset($methods[$type])) {
            return;
        }

        $this->results[$type] = $this->benchmark(
            fn() => $this->{$methods[$type]}(),
        );
    }

    private function benchmark(callable $fn): float
    {
        $start = microtime(true);
        $fn();
        return round((microtime(true) - $start) * 1000, 2);
    }

    private function renderPlain(): void
    {
        for ($i = 0; $i < 2000; $i++) {
            $x = "<div>{$i}</div>";
        }
    }

    private function renderForgeComponent(): void
    {
        for ($i = 0; $i < 2000; $i++) {
            Component::render("forge-ui:alert", loadFromModule: true);
        }
    }

    private function renderWireComponent(): void
    {
        for ($i = 0; $i < 1; $i++) {
            wire_name(name: "counter", componentId: "counter-{$i}");
        }
    }

    private function renderMixed(): void
    {
        for ($i = 0; $i < 2000; $i++) {
            Component::render("forge-ui:alert", loadFromModule: true);
            wire_name(name: "counter", componentId: "counter-{$i}");
        }
    }

    public function render(): string
    {
        return raw(
            $this->view("Metric/View", [
                "results" => $this->results,
            ]),
        );
    }
}
