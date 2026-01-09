<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeWire\Attributes\Computed;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Traits\ReactiveControllerHelper;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Traits\ControllerHelper;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Attributes\Middleware;

#[Reactive]
#[Middleware("web")]
final class SearchController
{
    use ControllerHelper;
    use ReactiveControllerHelper;

    #[State]
    public string $query = '';

    #[Action]
    public function calculate(): void
    {
        $this->total = $this->number1 + $this->number2;
    }

    #[State]
    public int $number1 = 0;
    #[State]
    public int $number2 = 0;

    public int $total = 0;

    #[Route("/search")]
    public function index(Request $request): Response
    {
        $user = User::query()->first();

        $results = $this->search($this->query);
        $data = [
            "results" => $results,
            "query" => $this->query,
            "total" => $this->total,
            "number1" => $this->number1,
            "number2" => $this->number2,
            "user" => $user
        ];

        return $this->view("pages/search/index", $data);
    }

    #[Action]
    public function search(string $query): array
    {
        if ($query === '') {
            return [];
        }

        return [
            (object) ['title' => "Result for: $query 1"],
            (object) ['title' => "Result for: $query 2"],
            (object) ['title' => "Result for: $query 3"],
        ];
    }
}
