<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Events\TestPagevisitedEvent;
use App\Modules\ForgeEvents\Exceptions\EventException;
use App\Modules\ForgeEvents\Services\EventDispatcher;
use App\Modules\ForgeMultiTenant\Attributes\TenantScope;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\CookieJar;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Core\Session\SessionInterface;
use Forge\Traits\ControllerHelper;
use Forge\Traits\ResponseHelper;

#[Service]
#[TenantScope("central")]
final class TestController
{
    use ControllerHelper;
    use ResponseHelper;

    public function __construct(
        private readonly SessionInterface $session,
        private readonly CookieJar        $cookies,
        private readonly EventDispatcher  $dispatcher
    )
    {
    }

    /**
     * @throws EventException
     */
    #[Route("/test")]
    public function index(Request $request): Response
    {
        $this->session->set("user_id", 123456);
        $cookie = $this->cookies->make('remember_me', 'token123', 60 * 24 * 30);

        $this->dispatcher->dispatch(
            new TestPagevisitedEvent(
                userId: $this->session->get('user_id'),
                visitedAt: date('Y-m-d H:i:s')
            )
        );

        $data = [
            "title" => "Welcome to Forge",
            "userId" => $this->session->get('user_id') ?? null
        ];

        return $this->view(view: "pages/test/index", data: $data)->withCookie($cookie);
    }

    #[Route("/test/failure")]
    public function failure(Request $request): Response
    {
        return $this->createErrorResponse($request, 'Simulate failure', 500);
    }
}
