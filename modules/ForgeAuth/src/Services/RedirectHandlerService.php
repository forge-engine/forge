<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use App\Modules\ForgeAuth\Contracts\ModuleRedirectInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Config\Config;
use Forge\Core\Session\SessionInterface;

#[Service]
final class RedirectHandlerService
{
  private const string INTENDED_URL_KEY = 'auth.intended_url';

  private static string $redirectAfterLogin;
  private static string $redirectAfterLogout;

  public function __construct(
    private readonly Config $config,
    private readonly SessionInterface $session,
    private readonly Container $container
  ) {
    self::$redirectAfterLogin = $this->config->get('forge_auth.auth.redirect.after_login', '/dashboard');
    self::$redirectAfterLogout = $this->config->get('forge_auth.auth.redirect.after_logout', '/');
  }

  /**
   * Store the intended URL that the user was trying to access before being redirected to login.
   */
  public function storeIntendedUrl(string $url): void
  {
    if (!$this->session->isStarted()) {
      $this->session->start();
    }
    $this->session->set(self::INTENDED_URL_KEY, $url);
  }

  /**
   * Get the intended URL that was stored before redirecting to login.
   */
  public function getIntendedUrl(): ?string
  {
    if (!$this->session->isStarted()) {
      $this->session->start();
    }
    return $this->session->get(self::INTENDED_URL_KEY);
  }

  /**
   * Clear the stored intended URL.
   */
  public function clearIntendedUrl(): void
  {
    if (!$this->session->isStarted()) {
      $this->session->start();
    }
    $this->session->remove(self::INTENDED_URL_KEY);
  }

  /**
   * Resolve the redirect URL after login with priority:
   * 1. Intended URL (from session)
   * 2. Module-specific redirect (if intended URL matches a module's route)
   * 3. Config default
   */
  public function redirectAfterLogin(): string
  {
    $intendedUrl = $this->getIntendedUrl();

    // Priority 1: Use intended URL if available
    if ($intendedUrl !== null) {
      // Priority 2: Check if any module wants to override this redirect
      $moduleRedirect = $this->getModuleRedirect($intendedUrl);
      if ($moduleRedirect !== null) {
        $this->clearIntendedUrl();
        return $moduleRedirect;
      }

      // Use intended URL
      $this->clearIntendedUrl();
      return $intendedUrl;
    }

    // Priority 3: Check for module redirects (even without intended URL)
    $moduleRedirect = $this->getModuleRedirect(null);
    if ($moduleRedirect !== null) {
      return $moduleRedirect;
    }

    // Fallback: Config default
    return self::$redirectAfterLogin;
  }

  /**
   * Get module-specific redirect URL.
   *
   * @param string|null $intendedUrl The intended URL, or null
   * @return string|null The module redirect URL, or null if no module wants to redirect
   */
  private function getModuleRedirect(?string $intendedUrl): ?string
  {
    // Get all registered services and check if any implement ModuleRedirectInterface
    // For now, we'll check the container for services that implement the interface
    // This is a simple approach - in the future, we could use a registry

    try {
      // Check if HubItemRegistry is available to get module routes
      if ($this->container->has(\App\Modules\ForgeHub\Services\HubItemRegistry::class)) {
        $registry = $this->container->get(\App\Modules\ForgeHub\Services\HubItemRegistry::class);
        $hubItems = $registry->getHubItems();

        // If we have an intended URL, find which module it belongs to
        if ($intendedUrl !== null) {
          foreach ($hubItems as $item) {
            $route = parse_url($item['route'], PHP_URL_PATH);
            $intendedPath = parse_url($intendedUrl, PHP_URL_PATH);

            // Check if intended URL matches this hub item's route
            if ($route === $intendedPath || str_starts_with($intendedPath ?? '', $route)) {
              // Try to get the module class and check if it implements ModuleRedirectInterface
              $moduleClass = $item['module'] ?? null;
              if ($moduleClass && class_exists($moduleClass)) {
                $reflection = new \ReflectionClass($moduleClass);
                if ($reflection->implementsInterface(ModuleRedirectInterface::class)) {
                  $moduleInstance = $this->container->get($moduleClass);
                  if ($moduleInstance instanceof ModuleRedirectInterface) {
                    $redirect = $moduleInstance->getRedirectAfterLogin($intendedUrl);
                    if ($redirect !== null) {
                      return $redirect;
                    }
                  }
                }
              }
            }
          }
        }
      }
    } catch (\Throwable) {
      // If anything fails, just continue to fallback
    }

    return null;
  }

  public static function redirectAfterLogout(): string
  {
    return self::$redirectAfterLogout;
  }

  public function handleRedirect(): array
  {
    return [
      'after_login' => $this->redirectAfterLogin(),
      'after_logout' => self::$redirectAfterLogout,
    ];
  }
}
