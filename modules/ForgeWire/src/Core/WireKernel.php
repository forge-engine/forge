<?php

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Support\Checksum;
use Forge\Core\DI\Container;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Exceptions\ValidationException;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Validation\Validator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use ReflectionNamedType;

final class WireKernel
{
    private static array $reflCache = [];
    private static array $actionCache = [];

    public function __construct(
        private Container $container,
        private Hydrator $hydrator,
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

        if ($class === "" || !class_exists($class)) {
            return ["ignored" => true, "id" => $id];
        }

        if (!isset(self::$reflCache[$class])) {
            $refl = new ReflectionClass($class);
            self::$reflCache[$class] = !empty($refl->getAttributes(Reactive::class));
        }

        if (!self::$reflCache[$class]) {
            return ["ignored" => true, "id" => $id];
        }

        $this->checksum->verify(
            $p["checksum"] ?? null,
            $sessionKey,
            $session,
            $ctx,
        );

        $instance = $this->container->make($class);

        $dirty = $this->filterDirty($dirty, $session, $sessionKey);

        $isSubmit =
            $action !== null
            && $action !== 'input'
            && $this->isSubmitAction($class, $action);

        $shouldValidateState =
            $action === 'input'
            || $isSubmit;

        if ($shouldValidateState) {
            $errors = $this->validateReactiveState(
                $instance,
                $dirty,
                $class,
                $isSubmit
            );

            if ($errors !== []) {
                return [
                    "html" => "",
                    "state" => null,
                    "checksum" => $this->checksum->sign($sessionKey, $session, $ctx),
                    "events" => [],
                    "redirect" => null,
                    "flash" => [],
                    "errors" => $errors,
                ];
            }
        }

        $this->hydrator->hydrate($instance, $dirty, $session, $sessionKey);

        $html = "";

        if ($action === "input" && !method_exists($instance, "input")) {
            $action = $session->get("forgewire:{$id}:action") ?? "index";
        }

        if ($action) {
            $html = $this->callAction($instance, $action, $request, $session, $args, $dirty, true, $id);
        }

        if ($html === "") {
            $renderAction = $session->get("forgewire:{$id}:action") ?? "index";
            if (method_exists($instance, $renderAction)) {
                $html = $this->callAction($instance, $renderAction, $request, $session, $args, $dirty, false, $id);
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

    private function callAction($instance, string $action, Request $request, SessionInterface $session, array $args, array $dirty, bool $isExplicitAction, string $id): string
    {
        $class = $instance::class;
        $cacheKey = "{$class}::{$action}";

        if (!isset(self::$actionCache[$cacheKey])) {
            if (!method_exists($instance, $action)) {
                self::$actionCache[$cacheKey] = false;
                return "";
            }

            $rm = new ReflectionMethod($instance, $action);
            if (!$rm->isPublic()) {
                throw new RuntimeException("Action method must be public: {$action}");
            }

            $isAction = !empty($rm->getAttributes(Action::class));
            $params = [];
            foreach ($rm->getParameters() as $param) {
                $typeName = null;
                if ($param->hasType()) {
                    $type = $param->getType();
                    if ($type instanceof ReflectionNamedType) {
                        $typeName = ltrim($type->getName(), '\\');
                    }
                }
                $params[] = [
                    'name' => $param->getName(),
                    'type' => $typeName,
                ];
            }

            self::$actionCache[$cacheKey] = [
                'rm' => $rm,
                'isAction' => $isAction,
                'params' => $params,
            ];
        }

        $meta = self::$actionCache[$cacheKey];
        if ($meta === false) {
            return "";
        }

        /** @var ReflectionMethod $rm */
        $rm = $meta['rm'];

        if ($isExplicitAction) {
            $originalAction = $session->get("forgewire:{$id}:action") ?? "index";
            if ($action !== $originalAction && !$meta['isAction']) {
                throw new RuntimeException("Action not allowed: {$action}. Must be marked with #[Action].");
            }
        }

        $methodArgs = [];
        foreach ($meta['params'] as $i => $pMeta) {
            $name = $pMeta['name'];
            $typeName = $pMeta['type'];
            $v = null;

            if ($typeName !== null) {
                if ($typeName === ltrim(Request::class, '\\'))
                    $v = $request;
                elseif ($typeName === ltrim(SessionInterface::class, '\\'))
                    $v = $session;
            }

            if ($v === null) {
                $v = $args[$i] ?? $args[$name] ?? $dirty[$name] ?? null;
                if ($typeName !== null) {
                    if ($typeName === "int" && $v !== null && is_string($v))
                        $v = (int) $v;
                    elseif ($typeName === "float" && $v !== null && is_string($v))
                        $v = (float) $v;
                    elseif ($typeName === "bool" && $v !== null && is_string($v))
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                }
            }
            $methodArgs[] = $v;
        }

        $res = $rm->invokeArgs($instance, $methodArgs);
        if ($res instanceof Response) {
            return $res->getContent();
        }
        return (string) $res;
    }

    private function isSubmitAction(string $class, string $action): bool
    {
        $rm = new ReflectionMethod($class, $action);

        foreach ($rm->getAttributes(Action::class) as $attr) {
            $instance = $attr->newInstance();
            return $instance->submit ?? false;
        }

        return false;
    }

    private function validateReactiveState(
        object $instance,
        array $dirty,
        string $class,
        bool $isSubmit
    ): array {
        $recipe = Hydrator::getRecipe($class);

        $data = [];
        $rules = [];
        $messages = [];

        foreach ($recipe as $prop => $cfg) {
            if (
                ($cfg['kind'] ?? null) !== 'state'
                || !isset($cfg['validate'])
            ) {
                continue;
            }

            if (
                !array_key_exists($prop, $dirty)
                && !$isSubmit
            ) {
                continue;
            }

            $value = array_key_exists($prop, $dirty)
                ? $dirty[$prop]
                : $cfg['reader']($instance);

            $data[$prop] = $value;
            $rules[$prop] = $cfg['validate']['rules'];

            if (!empty($cfg['validate']['messages'])) {
                $messages[$prop] = $cfg['validate']['messages'];
            }
        }

        if ($data === []) {
            return [];
        }

        $flatMessages = [];

        foreach ($messages as $field => $fieldMessages) {
            foreach ($fieldMessages as $rule => $message) {
                $flatMessages["{$field}.{$rule}"] = $message;
            }
        }

        try {
            (new Validator(
                data: $data,
                rules: $rules,
                messages: $flatMessages,
                onlyPresent: !$isSubmit
            ))->validate();

            return [];
        } catch (ValidationException $e) {
            return $e->errors();
        }
    }

    private function filterDirty(
        array $dirty,
        SessionInterface $session,
        string $sessionKey
    ): array {
        $stateBag = $session->get($sessionKey, []);
        $filtered = [];

        foreach ($dirty as $key => $value) {
            if (!array_key_exists($key, $stateBag)) {
                continue;
            }

            if ($stateBag[$key] !== $value) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function actionTouchesValidatedState(
        string $class,
        ?string $action,
        array $dirty
    ): bool {
        if ($action === null) {
            return false;
        }

        $recipe = Hydrator::getRecipe($class);

        foreach ($dirty as $prop => $_) {
            if (
                isset($recipe[$prop]) &&
                ($recipe[$prop]['kind'] ?? null) === 'state' &&
                isset($recipe[$prop]['validate'])
            ) {
                return true;
            }
        }

        return false;
    }
}
