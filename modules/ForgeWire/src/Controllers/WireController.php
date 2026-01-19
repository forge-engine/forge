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
    $payload = $request->json();
    $componentId = $payload['id'] ?? null;

    try {
      if ($componentId) {
        $this->trackActiveComponent($componentId);
      }

      $result = $this->kernel->process($payload, $request, $this->session);

      if (random_int(1, 20) === 1) {
        $this->cleanupStaleComponents();
      }

      if (random_int(1, 10) === 1) {
        $this->gcEmptyComponents();
      }

      return $this->jsonResponse($result);
    } catch (\RuntimeException $e) {
      $isChecksumMismatch = str_contains($e->getMessage(), 'checksum mismatch')
        || str_contains($e->getMessage(), 'Fingerprint mismatch');

      if ($isChecksumMismatch && $componentId !== null) {
        $requestKey = $this->getRequestKey($payload);
        $processingKey = "forgewire:processing:{$requestKey}";

        if ($this->session->has($processingKey)) {
          $processingTime = $this->session->get($processingKey);
          if (time() - $processingTime < 5) {
            return $this->jsonResponse(["ignored" => true, "id" => $componentId]);
          }
        }

        $sessionKey = "forgewire:{$componentId}";
        $state = $this->session->get($sessionKey, []);

        if (empty($state)) {
          $this->logger->debug('ForgeWire checksum mismatch due to missing state - allowing re-initialization', [
            'component_id' => $componentId,
            'exception' => get_class($e),
          ]);

          return $this->jsonResponse([
            'needs_reinit' => true,
            'id' => $componentId,
            'message' => 'Component state missing - re-initialization required',
          ], 200);
        }
      }

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

  private function getRequestKey(array $payload): string
  {
    $id = $payload['id'] ?? '';
    $action = $payload['action'] ?? null;
    $args = $payload['args'] ?? [];
    $checksum = $payload['checksum'] ?? '';

    $argsJson = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return md5("{$id}:{$action}:{$argsJson}:{$checksum}");
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
   * Optimized to reduce session operations
   */
  private function cleanupStaleComponents(): void
  {
    $allKeys = $this->session->all();
    $now = time();
    $staleThreshold = 200;

    $componentLastSeen = [];
    foreach ($allKeys as $key => $value) {
      if (preg_match('/^forgewire:active:([^:]+)$/', $key, $matches)) {
        $componentId = $matches[1];
        $lastSeen = is_numeric($value) ? (int) $value : null;
        $componentLastSeen[$componentId] = $lastSeen;
      }
    }

    foreach ($componentLastSeen as $componentId => $lastSeen) {
      if ($lastSeen === null || ($now - $lastSeen) > $staleThreshold) {
        $this->removeComponent($componentId);
      }
    }
  }

  /**
   * Remove a component and all its related session data
   * Optimized to batch session operations
   */
  private function removeComponent(string $componentId): void
  {
    $allKeys = $this->session->all();
    $prefix = "forgewire:{$componentId}";
    $keysToRemove = [];

    foreach ($allKeys as $key => $_) {
      if (str_starts_with($key, $prefix . ':') || $key === $prefix) {
        $keysToRemove[] = $key;
      }
    }

    foreach ($keysToRemove as $key) {
      $this->session->remove($key);
    }

    $this->removeFromSharedGroups($componentId);
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

    $groupKey = "forgewire:shared-group:{$componentClass}:components";
    if ($this->session->has($groupKey)) {
      $components = $this->session->get($groupKey, []);
      $components = array_filter($components, fn($id) => $id !== $componentId);
      $components = array_values($components);

      if (empty($components)) {
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
      if (preg_match('/^forgewire:[^:]+$/', $key)) {
        $components[] = $key;
      }
    }

    foreach ($components as $base) {
      $state = $this->session->get($base, []);
      if ($state === []) {
        $componentId = str_replace('forgewire:', '', $base);
        $this->removeComponent($componentId);
      }
    }
  }
}
