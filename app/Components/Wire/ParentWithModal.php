<?php

namespace App\Components\Wire;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Core\WireComponent;

final class ParentWithModal extends WireComponent
{
    #[State]
    public bool $show = false;
    #[State]
    public int $userId;

    public function mount(array $props = []): void
    {
        $this->userId = (int)($props['userId'] ?? 1);
    }

    #[Action]
    public function open(): void
    {
        $this->show = true;
    }

    #[Action]
    public function close(): void
    {
        $this->show = false;
    }

    public function render(): string
    {
        return $this->view('wire/parent-with-modal', [
            'show' => $this->show,
            'userId' => $this->userId,
        ]);
    }
}
