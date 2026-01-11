<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Services;

use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Support\Checksum;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Session\SessionInterface;

#[Service]
final class ComponentIdentityService
{
    /**
     * @var array<string, bool>
     */
    private static array $reflectionCache = [];

    public function __construct(
        private SessionInterface $session,
        private Checksum $checksum
    ) {
    }

    /**
     * Generates the ForgeWire checksum and signs the component identity in session.
     */
    public function getFingerprint(
        string $id,
        string $controllerClass,
        ?string $method = 'index',
        array $uses = []
    ): string {
        if (!$this->isReactive($controllerClass)) {
            return '';
        }

        $this->session->remove("forgewire:{$id}");
        $this->session->remove("forgewire:{$id}:models");
        $this->session->remove("forgewire:{$id}:dtos");

        $this->session->set("forgewire:{$id}:class", $controllerClass);
        $this->session->set("forgewire:{$id}:action", $method ?? 'index');

        if ($uses !== []) {
            \App\Modules\ForgeWire\Core\Hydrator::registerDependency(
                session: $this->session,
                class: $controllerClass,
                componentId: $id,
                states: $uses
            );
        }

        $sig = $this->checksum->sign("forgewire:{$id}", $this->session, [
            'class' => $controllerClass,
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
        ]);

        return ' fw:checksum="' . htmlspecialchars($sig) . '"';
    }

    /**
     * Check if a controller is ForgeWire compatible using reflection and static caching.
     */
    private function isReactive(string $class): bool
    {
        if (isset(self::$reflectionCache[$class])) {
            return self::$reflectionCache[$class];
        }

        try {
            if (!class_exists($class)) {
                return self::$reflectionCache[$class] = false;
            }

            $refl = new \ReflectionClass($class);
            $attributes = $refl->getAttributes(Reactive::class);

            return self::$reflectionCache[$class] = !empty($attributes);
        } catch (\Throwable) {
            return self::$reflectionCache[$class] = false;
        }
    }
}
