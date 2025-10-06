<?php

namespace App\Components;

use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Computed;
use App\Modules\ForgeWire\Core\WireComponent;

final class Counter extends WireComponent
{
    #[State] public int $count = 0;

    public function mount(array $props = []): void
    {
        $start = isset($props[0]) ? (int) $props[0] : 0;
        $this->count = $start;
    }

    #[Action]
    public function increment(): void
    {
        $this->count++;
    }
    #[Action]
    public function decrement(): void
    {
        $this->count--;
    }

    #[Computed]
    public function parity(): string
    {
        return $this->count % 2 ? 'odd' : 'even';
    }

    public function render(): string
    {
        return raw($this->view('Counter/View', [
            'count'  => $this->count,
            'parity' => $this->parity(),
        ]));
    }
}
