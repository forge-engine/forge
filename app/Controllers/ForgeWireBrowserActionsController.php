<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Traits\ReactiveControllerHelper;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Traits\ControllerHelper;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Middleware("web")]
#[Reactive]
final class ForgeWireBrowserActionsController
{
    use ControllerHelper;
    use ReactiveControllerHelper;

    #[Route("/forge-wire-browser-actions")]
    #[Action]
    public function index(): Response|string
    {
        return $this->view("pages/examples/browser-actions", []);
    }

    #[Action]
    public function testRedirect(): void
    {
        $this->redirect('/forge-wire-examples');
    }

    #[Action]
    public function testFlashSuccess(): void
    {
        $this->flash('success', 'This is a success message!');
    }

    #[Action]
    public function testFlashError(): void
    {
        $this->flash('error', 'This is an error message!');
    }

    #[Action]
    public function testFlashInfo(): void
    {
        $this->flash('info', 'This is an info message!');
    }

    #[Action]
    public function testFlashWarning(): void
    {
        $this->flash('warning', 'This is a warning message!');
    }

    #[Action]
    public function openModal(string $modalId, string $title = '', string $message = ''): void
    {
        $this->dispatch('openModal', [
            'id' => $modalId,
            'title' => $title,
            'message' => $message,
        ]);
    }

    #[Action]
    public function closeModal(): void
    {
        $this->dispatch('closeModal');
    }

    #[Action]
    public function showNotification(string $type = 'success', string $message = 'Notification triggered!'): void
    {
        $this->dispatch('showNotification', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    #[Action]
    public function triggerAnimation(string $selector = '.card', string $animation = 'fadeIn'): void
    {
        $this->dispatch('animateElement', [
            'selector' => $selector,
            'animation' => $animation,
        ]);
    }

    #[Action]
    public function combinedAction(): void
    {
        $this->flash('success', 'Action completed successfully!');
        $this->dispatch('closeModal');
        $this->redirect('/forge-wire-examples');
    }
}
