<?php

declare(strict_types=1);

namespace App\Components\Wire;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Service;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Core\WireComponent;
use App\Services\ProductService;

final class ProductSearch extends WireComponent
{
    #[State]
    public string $query = '';
    #[State]
    public array $results = [];
    #[State]
    public bool $open = false;
    #[State]
    public ?string $notice = null;

    #[Service(ProductService::class)]
    public ProductService $products;

    public function mount(array $props = []): void
    {
        $this->query = (string)($props['query'] ?? '');
    }

    #[Action]
    public function input(): void
    {
        $this->notice = null;
        $q = trim($this->query);
        if ($q === '') {
            $this->results = [];
            $this->open = false;
            return;
        }
        $list = $this->products->list($q, 'name', 'asc', 1, 6);
        $this->results = $list['items'];
        $this->open = true;
    }

    #[Action]
    public function choose(int $id): void
    {
        $p = $this->products->find($id);
        if ($p) {
            $this->query = $p->name;
            $this->notice = "Selected: {$p->name}";
        }
        $this->open = false;
        $this->results = [];
    }

    public function render(): string
    {
        return raw($this->view('wire/search-typeahead', [
            'query' => $this->query,
            'results' => $this->results,
            'open' => $this->open,
            'notice' => $this->notice,
        ]));
    }
}
