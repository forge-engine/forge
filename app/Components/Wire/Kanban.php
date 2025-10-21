<?php

namespace App\Components\Wire;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Core\WireComponent;

final class Kanban extends WireComponent
{
    #[State]
    public array $columns = [];
    #[State]
    public int $nextId = 1;

    #[State]
    public ?string $composeColumn = null;
    #[State]
    public string $newText = '';

    #[State]
    public ?int $editingId = null;
    #[State]
    public string $editText = '';

    public function mount(array $props = []): void
    {
        if (empty($this->columns)) {
            $this->columns = [
                ['id' => 'todo', 'title' => 'To Do', 'cards' => []],
                ['id' => 'doing', 'title' => 'Doing', 'cards' => []],
                ['id' => 'done', 'title' => 'Done', 'cards' => []],
            ];
        }

        if (($props['seed'] ?? false) && empty($this->columns[0]['cards'])) {
            $this->columns[0]['cards'] = [
                ['id' => $this->nextId++, 'title' => 'Design DB schema'],
                ['id' => $this->nextId++, 'title' => 'Auth flow notes'],
            ];
            $this->columns[1]['cards'] = [
                ['id' => $this->nextId++, 'title' => 'Wire runtime polish'],
            ];
        }
    }

    #[Action]
    public function compose(string $colId): void
    {
        $this->composeColumn = $colId;
        $this->newText = '';
    }

    #[Action]
    public function cancelCompose(): void
    {
        $this->composeColumn = null;
        $this->newText = '';
    }

    #[Action]
    public function create(string $colId): void
    {
        $title = trim($this->newText);
        if ($title === '') {
            return;
        }

        $ci = $this->colIndex($colId);
        if ($ci === null) {
            return;
        }

        $this->columns[$ci]['cards'][] = ['id' => $this->nextId++, 'title' => $title];

        $this->composeColumn = null;
        $this->newText = '';
    }

    private function colIndex(string $colId): ?int
    {
        foreach ($this->columns as $i => $c) {
            if ($c['id'] === $colId) {
                return $i;
            }
        }
        return null;
    }

    #[Action]
    public function edit(int $id): void
    {
        [$ci, $ti] = $this->locate($id);
        if ($ci === null) {
            return;
        }
        $this->editingId = $id;
        $this->editText = (string)$this->columns[$ci]['cards'][$ti]['title'];
    }

    private function locate(int $id): array
    {
        foreach ($this->columns as $ci => $col) {
            foreach ($col['cards'] as $ti => $t) {
                if ($t['id'] === $id) {
                    return [$ci, $ti];
                }
            }
        }
        return [null, null];
    }

    #[Action]
    public function saveEdit(int $id): void
    {
        [$ci, $ti] = $this->locate($id);
        if ($ci === null) {
            return;
        }

        $title = trim($this->editText);
        if ($title === '') {
            return;
        }

        $this->columns[$ci]['cards'][$ti]['title'] = $title;
        $this->editingId = null;
        $this->editText = '';
    }

    #[Action]
    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editText = '';
    }

    #[Action]
    public function remove(int $id): void
    {
        [$ci, $ti] = $this->locate($id);
        if ($ci === null) {
            return;
        }
        array_splice($this->columns[$ci]['cards'], $ti, 1);
        if ($this->editingId === $id) {
            $this->editingId = null;
            $this->editText = '';
        }
    }

    #[Action]
    public function moveUp(int $id): void
    {
        [$ci, $ti] = $this->locate($id);
        if ($ci === null || $ti <= 0) {
            return;
        }
        $cards = &$this->columns[$ci]['cards'];
        [$cards[$ti - 1], $cards[$ti]] = [$cards[$ti], $cards[$ti - 1]];
    }

    #[Action]
    public function moveDown(int $id): void
    {
        [$ci, $ti] = $this->locate($id);
        if ($ci === null) {
            return;
        }
        $cards = &$this->columns[$ci]['cards'];
        if ($ti >= count($cards) - 1) {
            return;
        }
        [$cards[$ti + 1], $cards[$ti]] = [$cards[$ti], $cards[$ti + 1]];
    }

    #[Action]
    public function moveLeft(int $id): void
    {
        [$ci, $ti] = $this->locate($id);
        if ($ci === null || $ci === 0) {
            return;
        }
        $card = $this->columns[$ci]['cards'][$ti];
        array_splice($this->columns[$ci]['cards'], $ti, 1);
        $this->columns[$ci - 1]['cards'][] = $card;
    }

    #[Action]
    public function moveRight(int $id): void
    {
        [$ci, $ti] = $this->locate($id);
        if ($ci === null || $ci >= count($this->columns) - 1) {
            return;
        }
        $card = $this->columns[$ci]['cards'][$ti];
        array_splice($this->columns[$ci]['cards'], $ti, 1);
        $this->columns[$ci + 1]['cards'][] = $card;
    }

    public function render(): string
    {
        return $this->view('wire/kanban', [
            'columns' => $this->columns,
            'editingId' => $this->editingId,
            'editText' => $this->editText,
            'composeCol' => $this->composeColumn,
            'newText' => $this->newText,
        ]);
    }
}
