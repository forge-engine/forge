<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Traits\ControllerHelper;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Response;

#[Middleware("web")]
#[Reactive]
final class PollController
{
    use ControllerHelper;

    #[State]
    public array $votes = [
        'PHP' => 5,
        'JS' => 3,
        'Python' => 2
    ];

    #[State]
    public bool $hasVoted = false;

    #[Route("/examples/poll")]
    public function index(): Response
    {
        return $this->view("pages/examples/poll", [
            'votes' => $this->votes,
            'hasVoted' => $this->hasVoted,
            'total' => array_sum($this->votes)
        ]);
    }

    #[Action]
    public function vote(string $lang): void
    {
        if ($this->hasVoted)
            return;

        if (isset($this->votes[$lang])) {
            $this->votes[$lang]++;
            $this->hasVoted = true;
        }
    }
}
