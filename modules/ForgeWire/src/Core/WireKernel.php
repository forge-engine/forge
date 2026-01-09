<?php

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Support\Checksum;
use App\Modules\ForgeWire\Support\Renderer;
use Forge\Core\DI\Container;
use Forge\Core\Http\Request;
use Forge\Core\Session\SessionInterface;

final class WireKernel
{
    public function __construct(
        private Container $container,
        private Hydrator $hydrator,
        private Renderer $renderer,
        private Checksum $checksum,
    ) {
    }

    public function process(array $p, Request $request, SessionInterface $session): array
    {
        $id = (string) ($p["id"] ?? "");
        $class = (string) ($p["controller"] ?? $session->get("forgewire:{$id}:class") ?? "");
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
            throw new \RuntimeException("Invalid controller. Session mapping might be missing or expired.");
        }

        $refl = new \ReflectionClass($class);
        if (empty($refl->getAttributes(\App\Modules\ForgeWire\Attributes\Reactive::class))) {
            throw new \RuntimeException("Controller is not marked as #[Reactive].");
        }

        $instance = $this->container->make($class);

        $this->hydrator->hydrate($instance, $dirty, $session, $sessionKey);

        $html = "";
        $redirect = null;

        if ($action === "input" && !method_exists($instance, "input")) {
            $action = $session->get("forgewire:{$id}:action") ?? "index";
        }

        if ($action) {
            $rm = null;
            if (method_exists($instance, $action)) {
                $rm = new \ReflectionMethod($instance, $action);
            }

            if ($rm) {
                if (!$rm->isPublic()) {
                    throw new \RuntimeException("Action method must be public: {$action}");
                }
                $originalAction = $session->get("forgewire:{$id}:action") ?? "index";
                if ($action !== $originalAction && empty($rm->getAttributes(\App\Modules\ForgeWire\Attributes\Action::class))) {
                    throw new \RuntimeException("Action not allowed: {$action}. Must be marked with #[Action].");
                }

                $methodArgs = [];
                $params = $rm->getParameters();

                foreach ($params as $i => $param) {
                    $name = $param->getName();
                    $v = null;

                    if ($param->hasType()) {
                        $type = $param->getType();
                        if ($type instanceof \ReflectionNamedType) {
                            $typeName = ltrim($type->getName(), '\\');
                            if ($typeName === ltrim(\Forge\Core\Http\Request::class, '\\')) {
                                $v = $request;
                            } elseif ($typeName === ltrim(\Forge\Core\Session\SessionInterface::class, '\\')) {
                                $v = $session;
                            }
                        }
                    }

                    if ($v === null) {
                        $v = $args[$i] ?? $args[$name] ?? $dirty[$name] ?? null;

                        if ($param->hasType()) {
                            $type = $param->getType();
                            if ($type instanceof \ReflectionNamedType) {
                                $typeName = $type->getName();
                                if ($typeName === "int" && $v !== null && is_string($v))
                                    $v = (int) $v;
                                if ($typeName === "float" && $v !== null && is_string($v))
                                    $v = (float) $v;
                                if ($typeName === "bool" && $v !== null && is_string($v))
                                    $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                            }
                        }
                    }
                    $methodArgs[] = $v;
                }

                $response = $rm->invokeArgs($instance, $methodArgs);

                if ($response instanceof \Forge\Core\Http\Response) {
                    $html = $response->getContent();
                } elseif (is_string($response)) {
                    $html = $response;
                }
            } else {
                if ($action !== "input") {
                    throw new \RuntimeException("Action method not found: {$action}");
                }
            }
        }

        if ($html === "" && method_exists($instance, 'render')) {
            $html = (string) $instance->render();
        }

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
