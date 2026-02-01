<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeAuth\Traits\HasCurrentUser;
use App\Modules\ForgeAuth\Traits\HasRoles;
use App\Modules\ForgeHub\Services\HubItemRegistry;
use App\Modules\ForgeHub\Services\LogService;
use App\Modules\ForgeHub\Services\CacheService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\Framework;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Attributes\RequiresRole;
use Forge\Core\Http\Response;
use Forge\Core\Module\ModuleLoader\Loader;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware("web")]
#[Middleware("auth")]
#[Middleware("role")]
#[RequiresRole(Role::ADMIN->name)]
final class DashboardController
{
    use ControllerHelper;

    public function __construct(
        private readonly HubItemRegistry $registry,
        private readonly Loader $loader,
        private readonly LogService $logService,
        private readonly Container $container,
    ) {}

    #[Route("/hub")]
    public function index(): Response
    {
        $modules = $this->loader->getSortedModuleRegistry();
        $hubItems = $this->registry->getHubItems();
        $logFiles = $this->logService->getLogFiles();

        $cacheStats = null;
        if ($this->container->has(CacheService::class)) {
            try {
                $cacheService = $this->container->get(CacheService::class);
                $cacheStats = $cacheService->getStats();
            } catch (\Throwable) {
            }
        }

        $queueStats = null;
        if (
            $this->container->has(
                \App\Modules\ForgeEvents\Services\QueueHubService::class,
            )
        ) {
            try {
                $queueService = $this->container->get(
                    \App\Modules\ForgeEvents\Services\QueueHubService::class,
                );
                $queueStats = $queueService->getStats();
            } catch (\Throwable) {
            }
        }

        $data = [
            "title" => "Dashboard",
            "phpVersion" => phpversion(),
            "frameworkVersion" => Framework::version(),
            "moduleCount" => count($modules),
            "hubItemCount" => count($hubItems),
            "logFileCount" => count($logFiles),
            "cacheStats" => $cacheStats,
            "queueStats" => $queueStats,
            "hubItems" => $hubItems,
        ];

        return $this->view(view: "pages/dashboard", data: $data);
    }
}
