<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeHub\Services\CacheService;
use App\Modules\ForgeHub\Services\EnhancedCacheService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Middleware('hub-permissions')]
final class CacheController
{
  use ControllerHelper;

  public function __construct(
    private readonly CacheService $cacheService,
    private readonly EnhancedCacheService $enhancedCacheService
  ) {
  }

  #[Route("/hub/cache")]
  public function index(Request $request): Response
  {
    $stats = $this->cacheService->getStats();
    $details = $this->enhancedCacheService->getDetailedStats();
    $tags = $this->enhancedCacheService->getAvailableTags();

    $data = [
      'title' => 'Cache Management',
      'stats' => $stats,
      'details' => $details,
      'tags' => $tags,
    ];

    return $this->view(view: "pages/cache", data: $data);
  }

  #[Route("/hub/cache/clear", "POST")]
  public function clear(Request $request): Response
  {
    $this->cacheService->clearAll();
    
    return $this->jsonResponse([
      'success' => true,
      'message' => 'Cache cleared successfully',
    ]);
  }





  #[Route("/hub/cache/clear-expired", "POST")]
  public function clearExpired(Request $request): Response
  {
    $hours = (int)($request->postData['hours'] ?? 24);
    $this->cacheService->clearExpired($hours);
    
    return $this->jsonResponse([
      'success' => true,
      'message' => "Cleared cache entries older than {$hours} hours",
    ]);
  }

  #[Route("/hub/cache/clear-tag", "POST")]
  public function clearTag(Request $request): Response
  {
    $tag = $request->postData['tag'] ?? null;

    if (!$tag) {
      return $this->jsonResponse([
        'success' => false,
        'message' => 'Tag is required',
      ], 400);
    }

    $this->enhancedCacheService->clearByTag($tag);

    return $this->jsonResponse([
      'success' => true,
      'message' => "Cache tag '{$tag}' cleared successfully",
    ]);
  }
}
