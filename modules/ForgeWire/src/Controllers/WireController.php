<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Controllers;

use App\Modules\ForgeWire\Core\WireKernel;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Core\Session\SessionInterface;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware("web")]
final class WireController
{
    use ControllerHelper;

    public function __construct(
        private WireKernel $kernel,
        private SessionInterface $session
    ) {
    }

    #[Route("/__wire", method: 'POST')]
    public function handle(Request $request): Response
    {
        $payload = $request->json();
        $result  = $this->kernel->process($payload, $this->session);
        $this->gcEmptyComponents();
        return $this->jsonResponse($result);
    }

    private function gcEmptyComponents(): void
    {
        $allKeys = $this->session->all();
        $components = [];
        foreach ($allKeys as $key => $_) {
            if (preg_match('/^forgewire:fw-[a-f0-9]+$/', $key)) {
                $components[] = $key;
            }
        }

        foreach ($components as $base) {
            $state = $this->session->get($base, []);
            if ($state === []) {
                $this->session->remove($base);
                $this->session->remove($base . ':models');
                $this->session->remove($base . ':dtos');
                $this->session->remove($base . ':fp');
                $this->session->remove($base . ':sig');
            }
        }
    }
}
