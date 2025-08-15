<?php

namespace App\Components;

use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Core\WireComponent;

final class SortableList extends WireComponent
{
    /** @var array<int, array{id:int,title:string}> */
    #[State] public array $items = [];

    public function mount(array $props = []): void
    {
        $this->items = $props['items'] ?? [
            ['id'=>1,'title'=>'First'],
            ['id'=>2,'title'=>'Second'],
            ['id'=>3,'title'=>'Third'],
        ];
    }

    #[Action] public function moveUp(int $id): void
    {
        $i = $this->findIndex($id);
        if ($i <= 0) {
            return;
        }
        [$this->items[$i-1], $this->items[$i]] = [$this->items[$i], $this->items[$i-1]];
        $this->items = array_values($this->items);
    }

    #[Action] public function moveDown(int $id): void
    {
        $i = $this->findIndex($id);
        if ($i === -1 || $i >= count($this->items)-1) {
            return;
        }
        [$this->items[$i+1], $this->items[$i]] = [$this->items[$i], $this->items[$i+1]];
        $this->items = array_values($this->items);
    }

    private function findIndex(int $id): int
    {
        foreach ($this->items as $i => $item) {
            if ($item['id'] === $id) {
                return $i;
            }
        }
        return -1;
    }

    public function render(): string
    {
        return $this->view('SortableList/View', ['items'=>$this->items]);
    }
}
