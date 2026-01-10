<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Attributes\Validate;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Traits\ControllerHelper;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Middleware("web")]
#[Reactive]
final class TodoListController
{
    use ControllerHelper;

    #[State]
    public array $todos = [
        ['id' => 1, 'text' => 'Learn Forge Framework', 'done' => true],
        ['id' => 2, 'text' => 'Master ForgeWire', 'done' => false],
    ];

    #[State]
    #[Validate('required|min:3|max:6')]
    public string $newTask = '';

    #[Route("/examples/todo")]
    public function index(Request $request): Response
    {
        return $this->view("pages/examples/todo", [
            'todos' => $this->todos,
            'newTask' => $this->newTask,
        ]);
    }

    #[Action(submit: true)]
    public function addTodo(): void
    {
        if (trim($this->newTask) === '')
            return;

        $this->todos[] = [
            'id' => time(),
            'text' => $this->newTask,
            'done' => false
        ];

        $this->newTask = '';
    }

    #[Action]
    public function toggleTodo(int $id): void
    {
        foreach ($this->todos as &$todo) {
            if ($todo['id'] === $id) {
                $todo['done'] = !$todo['done'];
                break;
            }
        }
    }

    #[Action]
    public function removeTodo(int $id): void
    {
        $this->todos = array_filter($this->todos, fn($t) => $t['id'] !== $id);
    }
}
