<?php

declare(strict_types=1);

namespace App\Components;

use App\DTO\ProductDTO;
use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Service;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Core\WireComponent;
use App\Services\ProductService;

final class ProductsTable extends WireComponent
{
    // filters / ui state
    #[State] public string $query   = '';
    #[State] public string $sort    = 'name';
    #[State] public string $dir     = 'asc';
    #[State] public int    $page    = 1;
    #[State] public int    $perPage = 5;
    #[State] public string $testCheck = 'initial_value';

    // edit form state
    #[State] public ?int    $editingId   = null;
    #[State] public string  $draftName   = '';
    #[State] public string  $draftPrice  = '';
    #[State] public array   $errors      = [];
    #[State] public ?string $notice      = null;

    #[Service(ProductService::class)]
    protected ProductService $products;

    public function mount(array $props = []): void
    {
        $this->perPage = (int)($props['perPage'] ?? 5);
    }

    #[Action]
    public function setSort(string $field): void
    {
        $this->notice = null;
        if ($this->sort === $field) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->dir  = 'asc';
        }
        $this->page = 1;
    }

    #[Action]
    public function goTo(int $page): void
    {
        $this->page = max(1, $page);
    }

    #[Action]
    public function edit(int $id): void
    {
        $this->errors = [];
        $this->notice = null;

        $p = $this->products->find($id);
        if (!$p) {
            return;
        }

        $this->editingId  = $id;
        $this->draftName  = $p->name;
        $this->draftPrice = (string)$p->price;
    }

    #[Action]
    public function cancel(): void
    {
        $this->editingId = null;
        $this->errors = [];
    }

    #[Action]
    public function save(): void
    {
        $this->errors = [];

        $name  = trim($this->draftName);
        $price = is_numeric($this->draftPrice) ? (float)$this->draftPrice : null;

        if ($name === '') {
            $this->errors['name'] = 'Name is required.';
        }
        if ($price === null || $price < 0) {
            $this->errors['price'] = 'Price must be a number ≥ 0.';
        }

        if ($this->errors) {
            return;
        }

        $dto = new ProductDTO($this->editingId ?? 0, $name, (float)$price);
        if ($dto->id === 0) {
            $dto->id = random_int(1000, 9999);
        }
        $this->products->save($dto);

        $this->notice = 'Saved ✔';
        $this->editingId = null;
        $this->testCheck = bin2hex(random_bytes(4));
    }

    #[Action]
    public function remove(int $id): void
    {
        $this->products->delete($id);
        $this->notice = 'Deleted ✔';
        $this->editingId = null;

        $res   = $this->products->list($this->query, $this->sort, $this->dir, $this->page, $this->perPage);
        $pages = max(1, (int)ceil($res['total'] / max(1, $this->perPage)));
        if ($this->page > $pages) {
            $this->page = $pages;
        }
    }

    public function render(): string
    {
        $list = $this->products->list($this->query, $this->sort, $this->dir, $this->page, $this->perPage);
        $total = $list['total'];
        $pages = max(1, (int)ceil($total / max(1, $this->perPage)));

        return raw($this->view('Products/Table', [
            'items'     => $list['items'],
            'total'     => $total,
            'pages'     => $pages,
            'page'      => $this->page,
            'perPage'   => $this->perPage,
            'sort'      => $this->sort,
            'dir'       => $this->dir,
            'query'     => $this->query,
            'editingId' => $this->editingId,
            'draftName' => $this->draftName,
            'draftPrice'=> $this->draftPrice,
            'errors'    => $this->errors,
            'notice'    => $this->notice,
        ]));
    }
}
