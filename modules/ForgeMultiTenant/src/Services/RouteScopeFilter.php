<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Services;

use Forge\Core\DI\Container;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;

final class RouteScopeFilter
{
    private static ?bool $isCentral = null;

    /**
     * @throws \ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    public static function isCentralDomain(): bool
    {
        return self::$isCentral ??= (new TenantManager(Container::getInstance()))
                ->resolveByDomain($_SERVER['HTTP_HOST'] ?? '') === null;
    }
}