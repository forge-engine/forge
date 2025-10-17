<?php

namespace App\Components;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Core\WireComponent;

final class ChildProfileEditor extends WireComponent
{
    #[State] public int $userId = 0;
    #[State] public string $name = '';
    #[State] public string $role = '';
    #[State] public string $notice = '';

    public function mount(array $props = []): void
    {
        $this->userId = (int)($props['userId'] ?? 0);
        $this->name = "User #{$this->userId}";
        $this->role = "member";
    }

    #[Action] public function save(): void
    {
        $this->notice = 'Saved!';
    }

    public function render(): string
    {
        return $this->view('ChildProfileEditor/View', [
            'name'=>$this->name, 'role'=>$this->role, 'notice'=>$this->notice
        ]);
    }
}
