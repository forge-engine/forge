<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Controllers;

use App\Modules\ForgeLogger\Services\ForgeLoggerService;
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
    private SessionInterface $session,
    private ForgeLoggerService $logger
  ) {
  }

  #[Route("/__wire", method: 'POST')]
  public function handle(Request $request): Response
  {
    try {
      $payload = $request->json();
      $componentId = $payload['id'] ?? null;

      // Track active component
      if ($componentId) {
        $this->trackActiveComponent($componentId);
      }

      $result = $this->kernel->process($payload, $request, $this->session);

      // Cleanup stale components (with probability to avoid overhead on every request)
      if (random_int(1, 10) === 1) { // 10% chance per request
        $this->cleanupStaleComponents();
      }

      $this->gcEmptyComponents();
      return $this->jsonResponse($result);
    } catch (\Throwable $e) {
      $this->logger->debug('ForgeWire error: ' . $e->getMessage(), [
        'exception' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ]);

      $isDebug = env('APP_DEBUG', false);

      $errorResponse = [
        'error' => [
          'message' => $isDebug ? $e->getMessage() : 'An error occurred processing your request.',
          'type' => get_class($e),
        ],
      ];

      if ($isDebug) {
        $errorResponse['error']['file'] = $e->getFile();
        $errorResponse['error']['line'] = $e->getLine();
      }

      return $this->jsonResponse($errorResponse, 500);
    }
  }

  /**
   * Track that a component is currently active
   */
  private function trackActiveComponent(string $componentId): void
  {
    $activeKey = "forgewire:active:{$componentId}";
    $this->session->set($activeKey, time());
  }

  /**
   * Clean up components that haven't been seen recently
   */
  private function cleanupStaleComponents(): void
  {
    $allKeys = $this->session->all();
    $now = time();
    $staleThreshold = 300; // 5 minutes

    // Find all component IDs
    $componentIds = [];
    foreach ($allKeys as $key => $_) {
      if (preg_match('/^forgewire:([^:]+)$/', $key, $matches)) {
        $componentIds[$matches[1]] = true;
      }
    }

    foreach (array_keys($componentIds) as $componentId) {
      $activeKey = "forgewire:active:{$componentId}";
      $lastSeen = $this->session->get($activeKey);

      // If component hasn't been seen recently, clean it up
      if ($lastSeen === null || ($now - $lastSeen) > $staleThreshold) {
        $this->removeComponent($componentId);
      }
    }
  }

  /**
   * Remove a component and all its related session data
   */
  private function removeComponent(string $componentId): void
  {
    $allKeys = array_keys($this->session->all());
    $prefix = "forgewire:{$componentId}";

    // Remove all keys related to this component (including :actions:* keys)
    foreach ($allKeys as $key) {
      if (str_starts_with($key, $prefix . ':') || $key === $prefix) {
        $this->session->remove($key);
      }
    }

    // Remove from shared groups
    $this->removeFromSharedGroups($componentId);

    // Remove active tracking
    $this->session->remove("forgewire:active:{$componentId}");
  }

  /**
   * Remove component from shared groups and clean up empty groups
   */
  private function removeFromSharedGroups(string $componentId): void
  {
    $allKeys = array_keys($this->session->all());
    $componentClass = $this->session->get("forgewire:{$componentId}:class");

    if (!$componentClass) {
      return;
    }

    // Find shared groups for this component class
    $groupKey = "forgewire:shared-group:{$componentClass}:components";
    if ($this->session->has($groupKey)) {
      $components = $this->session->get($groupKey, []);
      $components = array_filter($components, fn($id) => $id !== $componentId);
      $components = array_values($components);

      if (empty($components)) {
        // Remove entire shared group if empty
        $this->session->remove($groupKey);
        $this->session->remove("forgewire:shared-group:{$componentClass}:initialized");
        $this->session->remove("forgewire:shared:{$componentClass}");
      } else {
        $this->session->set($groupKey, $components);
      }
    }
  }

  /**
   * Clean up components with empty state (legacy method, kept for compatibility)
   */
  private function gcEmptyComponents(): void
  {
    $allKeys = $this->session->all();
    $components = [];
    foreach ($allKeys as $key => $_) {
      // Match base component session keys (not special keys like :class, :action, etc.)
      if (preg_match('/^forgewire:[^:]+$/', $key)) {
        $components[] = $key;
      }
    }

    foreach ($components as $base) {
      $state = $this->session->get($base, []);
      if ($state === []) {
        // Extract component ID from key (forgewire:componentId)
        $componentId = str_replace('forgewire:', '', $base);
        $this->removeComponent($componentId);
      }
    }
  }
}
