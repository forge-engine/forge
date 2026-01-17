<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeHub\Services\HubItemRegistry;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\ModuleLoader\Loader;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;
use ReflectionClass;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Middleware('hub-permissions')]
final class ModuleController
{
  use ControllerHelper;

  public function __construct(
    private readonly HubItemRegistry $registry,
    private readonly Loader $loader
  ) {
  }

  #[Route("/hub/modules")]
  public function index(Request $request): Response
  {
    $modules = $this->loader->getSortedModuleRegistry();
    $modulesData = [];

    foreach ($modules as $moduleInfo) {
      $className = $moduleInfo['name'];
      $modulePath = $moduleInfo['path'] ?? null;

      try {
        $reflection = new ReflectionClass($className);
        $moduleAttributes = $reflection->getAttributes(Module::class);

        if (empty($moduleAttributes)) {
          continue;
        }

        $moduleInstance = $moduleAttributes[0]->newInstance();
        $hubItems = $this->registry->getHubItemsForModule($className);

        $modulesData[] = [
          'name' => $moduleInstance->name ?? $className,
          'version' => $moduleInstance->version ?? '0.0.0',
          'description' => $moduleInstance->description ?? '',
          'author' => $moduleInstance->author ?? '',
          'license' => $moduleInstance->license ?? '',
          'type' => $moduleInstance->type ?? 'generic',
          'tags' => $moduleInstance->tags ?? [],
          'className' => $className,
          'path' => $modulePath,
          'hubItems' => $hubItems,
        ];
      } catch (\ReflectionException) {
        continue;
      }
    }

    $data = [
      'title' => 'Modules',
      'modules' => $modulesData,
    ];

    return $this->view(view: "pages/modules", data: $data);
  }
}
