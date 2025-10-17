<?php

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Support\Checksum;
use App\Modules\ForgeWire\Support\Renderer;
use Forge\Core\DI\Container;
use Forge\Core\Session\SessionInterface;

final class WireKernel
{
    public function __construct(
        private Container $container,
        private Hydrator $hydrator,
        private Renderer $renderer,
        private Checksum $checksum,
    ) {}

    public function process(array $p, SessionInterface $session): array
    {
        $id = (string) ($p["id"] ?? "");
        $class = (string) ($p["component"] ?? "");
        $action = $p["action"] ?? null;
        $args = $p["args"] ?? [];
        $dirty = (array) ($p["dirty"] ?? []);

        $sessionKey = "forgewire:{$id}";
        $ctx = [
            "class" => $class,
            "path" => (string) ($p["fingerprint"]["path"] ?? "/"),
        ];

        $this->checksum->verify(
            $p["checksum"] ?? null,
            $sessionKey,
            $session,
            $ctx,
        );

        if ($class === "" || !class_exists($class)) {
            throw new \RuntimeException("Invalid component.");
        }

        /** @var WireComponent $instance */
        $instance = $this->container->make($class);

        $this->hydrator->hydrate($instance, $dirty, $session, $sessionKey);

        if ($action) {
            if (!method_exists($instance, $action)) {
                if ($action !== "input") {
                    throw new \RuntimeException("Action not found: {$action}");
                }
            } else {
                $rm = new \ReflectionMethod($instance, $action);
                if (
                    $action !== "input" &&
                    empty(
                        $rm->getAttributes(
                            Action::class,
                        )
                    )
                ) {
                    throw new \RuntimeException(
                        "Action not allowed: {$action}",
                    );
                }
                foreach ($rm->getParameters() as $i => $param) {
                    $v = $args[$i] ?? null;
                    if (is_array($v) || is_object($v)) {
                        throw new \RuntimeException("Invalid argument type.");
                    }
                }
                $rm->invokeArgs($instance, $args);
            }
        }

        $html = $this->renderer->render($instance, $id, $class);
        $state = $this->hydrator->dehydrate($instance, $session, $sessionKey);
        $sig = $this->checksum->sign($sessionKey, $session, $ctx);

        return [
            "html" => $html,
            "state" => $state,
            "checksum" => $sig,
            "events" => [],
            "redirect" => null,
            "flash" => [],
        ];
    }
}
