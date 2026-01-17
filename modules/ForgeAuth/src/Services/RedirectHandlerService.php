<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Config\Config;

#[Service]
final class RedirectHandlerService
{
  private static string $redirectAfterLogin;
  private static string $redirectAfterLogout;

  public function __construct(
    private readonly Config $config,
  ) {
    self::$redirectAfterLogin = $this->config->get('forge_auth.auth.redirect.after_login', '/dashboard');
    self::$redirectAfterLogout = $this->config->get('forge_auth.auth.redirect.after_logout', '/');
  }

  public static function redirectAfterLogin(): string
  {
    return self::$redirectAfterLogin;
  }

  public static function redirectAfterLogout(): string
  {
    return self::$redirectAfterLogout;
  }

  public function handleRedirect(): array
  {
    return [
      'after_login' => self::$redirectAfterLogin,
      'after_logout' => self::$redirectAfterLogout,
    ];
  }
}
