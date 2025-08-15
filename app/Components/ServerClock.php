<?php

namespace App\Components;

use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Core\WireComponent;

final class ServerClock extends WireComponent
{
    #[State] public string $now = '';

    public function mount(array $props = []): void
    {
        $this->now = date('H:i:s');
    }

    public function render(): string
    {
        $this->now = date('H:i:s');
        return $this->view('ServerClock/View', ['now'=>$this->now]);
    }
}
