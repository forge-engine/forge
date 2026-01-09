<?php

declare(strict_types=1);

namespace App\Controllers;

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

    #[Route("/search")]
    public function index(Request $request): Response
    {
        $results = $this->search($this->query);
        $data = [
            "results" => $results,
            "query" => $this->query
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
