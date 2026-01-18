<?php

namespace App\Modules\ForgeWire\Support;

use Forge\Core\Config\Config;
use Forge\Core\Session\SessionInterface;

final class Checksum
{
  private const K_SIG = ':sig';
  private const K_FP = ':fp';

  private string $appKey;

  public function __construct(private Config $config)
  {
    $this->appKey = (string) $config->get('security.app_key', '');

    if ($this->appKey === '') {
      $this->appKey = (string) $config->get('app.key', '');
    }

    if ($this->appKey === '') {
      $this->appKey = (string) env('APP_KEY', '');
    }

    if ($this->appKey === '') {
      throw new \RuntimeException('App key required for ForgeWire checksum. Please set APP_KEY in your .env file or config/security.php as "app_key".');
    }
  }

  private function canonicalize(mixed $v): mixed
  {
    if (is_array($v)) {
      $isAssoc = array_keys($v) !== range(0, count($v) - 1);
      if ($isAssoc) {
        ksort($v);
      }
      foreach ($v as $k => $val) {
        $v[$k] = $this->canonicalize($val);
      }
      return $v;
    }
    if (is_object($v)) {
      return $this->canonicalize(get_object_vars($v));
    }
    return $v;
  }

  private function canonicalJson(mixed $v): string
  {
    $v = $this->canonicalize($v);
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  private function compute(SessionInterface $session, string $sessionKey, array $ctx): string
  {
    $state = (array) $session->get($sessionKey, []);
    $models = (array) $session->get($sessionKey . ':models', []);
    $dtos = (array) $session->get($sessionKey . ':dtos', []);

    $payload = [
      'class' => (string) ($ctx['class'] ?? ''),
      'path' => (string) ($ctx['path'] ?? ''),
      'sid' => (string) $session->getId(),
      'v' => 1,
      'bags' => [
        'state' => $state,
        'models' => $models,
        'dtos' => $dtos,
      ],
    ];

    if (isset($ctx['action']) && $ctx['action'] !== null) {
      $payload['action'] = (string) $ctx['action'];
    }

    if (isset($ctx['args']) && is_array($ctx['args'])) {
      $payload['args'] = $ctx['args'];
    }

    $json = $this->canonicalJson($payload);
    return hash_hmac('sha256', $json, $this->appKey);
  }

  public function sign(string $sessionKey, SessionInterface $session, array $ctx): string
  {
    $sig = $this->compute($session, $sessionKey, $ctx);
    $session->set($sessionKey . self::K_FP, [
      'class' => (string) ($ctx['class'] ?? ''),
      'path' => (string) ($ctx['path'] ?? ''),
    ]);
    $session->set($sessionKey . self::K_SIG, $sig);
    return $sig;
  }

  /**
   * Verify client-provided signature and fingerprint.
   * First request may have null checksum → initialize fp and allow.
   * Detects session changes (fingerprint exists but state is empty) and allows re-initialization.
   */
  public function verify(?string $provided, string $sessionKey, SessionInterface $session, array $ctx): void
  {
    $fp = (array) $session->get($sessionKey . self::K_FP);

    if (empty($fp)) {
      $session->set($sessionKey . self::K_FP, [
        'class' => (string) ($ctx['class'] ?? ''),
        'path' => (string) ($ctx['path'] ?? ''),
      ]);
      return;
    }

    $state = (array) $session->get($sessionKey, []);
    if (empty($state)) {
      $session->remove($sessionKey . self::K_FP);
      $session->remove($sessionKey . self::K_SIG);
      $session->set($sessionKey . self::K_FP, [
        'class' => (string) ($ctx['class'] ?? ''),
        'path' => (string) ($ctx['path'] ?? ''),
      ]);
      return;
    }

    $expClass = (string) ($fp['class'] ?? '');
    $expPath = (string) ($fp['path'] ?? '');
    $curClass = (string) ($ctx['class'] ?? '');
    $curPath = (string) ($ctx['path'] ?? '');

    // Class must always match
    if (!hash_equals($expClass, $curClass)) {
      throw new \RuntimeException('Fingerprint mismatch.');
    }

    // Path mismatch: if path changed but state exists, update fingerprint to new path
    // This handles navigation between routes where the component persists
    if (!hash_equals($expPath, $curPath)) {
      // Update fingerprint with new path to allow navigation
      $session->set($sessionKey . self::K_FP, [
        'class' => $curClass,
        'path' => $curPath,
      ]);
      // Re-sign with new path
      $session->set($sessionKey . self::K_SIG, $this->compute($session, $sessionKey, $ctx));
    }

    $stored = (string) ($session->get($sessionKey . self::K_SIG) ?? '');
    if ($stored === '') {
      return;
    }

    $hasAction = isset($ctx['action']) && $ctx['action'] !== null;

    if ($hasAction) {
      $stateCtx = $ctx;
      unset($stateCtx['action'], $stateCtx['args']);
      $stateChecksum = $this->compute($session, $sessionKey, $stateCtx);

      if ($provided === null || !hash_equals($stateChecksum, (string) $provided)) {
        throw new \RuntimeException('ForgeWire checksum mismatch. Component state may have been tampered with.');
      }
    } else {
      $computed = $this->compute($session, $sessionKey, $ctx);

      if ($provided === null || !hash_equals($computed, (string) $provided)) {
        throw new \RuntimeException('ForgeWire checksum mismatch.');
      }
    }
  }

  public function computeActionSignature(string $action, array $args): string
  {
    $normalized = $this->normalizeArgs($args);
    $payload = [
      'action' => $action,
      'args' => $normalized,
    ];
    $json = $this->canonicalJson($payload);
    return hash_hmac('sha256', $json, $this->appKey);
  }

  public function storeExpectedAction(string $sessionKey, SessionInterface $session, string $action, array $args): void
  {
    $signature = $this->computeActionSignature($action, $args);
    $session->set($sessionKey . ':actions:' . $signature, true);
  }

  public function isExpectedAction(string $sessionKey, SessionInterface $session, string $action, array $args): bool
  {
    $signature = $this->computeActionSignature($action, $args);
    return $session->get($sessionKey . ':actions:' . $signature) === true;
  }

  private function normalizeArgs(array $args): array
  {
    $normalized = [];
    foreach ($args as $key => $value) {
      $normalizedKey = is_string($key) ? strtolower($key) : $key;
      $normalized[$normalizedKey] = $value;
    }
    ksort($normalized);
    return $normalized;
  }
}
