<?php

declare(strict_types=1);

namespace App\Modules\AppAuth;

use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Module;

#[Module(
    name: 'AppAuth',
    version: '1.0.0',
    description: 'Application-level auth configuration',
    order: 100,
)]
final class AppAuthModule
{
    public function register(Container $container): void
    {
        ForgeAuthService::addCustomClaimsCallback(
            static function ($user): array {
                return [
                    'email'      => $user->email,
                    'identifier' => $user->identifier,
                    'tenant_id'  => $user->tenant_id ?? null,
                ];
            }
        );
    }
}

