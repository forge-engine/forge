<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Attributes\Validate;
use Forge\Core\DI\Container;
use Forge\Core\Session\SessionInterface;

final class Hydrator
{
    private static array $recipe = [];
    private static array $validateRules = [];

    public function __construct(private Container $container)
    {
    }

    public static function getRecipe(string $class): array
    {
        if (!isset(self::$recipe[$class])) {
            self::$recipe[$class] = self::buildRecipe($class);
        }

        return self::$recipe[$class];
    }

    public function hydrate(
        object $instance,
        array $dirty,
        SessionInterface $session,
        string $sessionKey,
        string $sharedKey,
    ): void {
        $class = $instance::class;

        if (!isset(self::$recipe[$class])) {
            self::$recipe[$class] = self::buildRecipe($class);
        }
        $recipe = self::$recipe[$class];

        $dtoInput = [];
        foreach ($dirty as $k => $v) {
            if (str_contains($k, ".")) {
                [$top, $field] = explode(".", $k, 2);
                $dtoInput[$top][$field] = $v;
            }
        }

        $sharedBag = $session->get($sharedKey, []);
        $localBag = $session->get($sessionKey, []);

        foreach ($recipe as $propName => $cfg) {
            $value = null;

            if ($cfg['kind'] === 'state') {
                if ($cfg['shared']) {
                    $value = $dirty[$propName]
                        ?? $sharedBag[$propName]
                        ?? $localBag[$propName]
                        ?? null;

                    if (array_key_exists($propName, $dirty)) {
                        $sharedBag[$propName] = $value;
                    }
                } else {
                    $value = $dirty[$propName]
                        ?? $localBag[$propName]
                        ?? null;

                    $localBag[$propName] = $value;
                }
            }

            if ($value !== null || array_key_exists($propName, $dirty)) {
                $cfg["accessor"]($instance, $value);
            }
        }

        $session->set($sharedKey, $sharedBag);
        $session->set($sessionKey, $localBag);
    }

    public function dehydrate(
        object $instance,
        SessionInterface $session,
        string $sessionKey,
        string $sharedKey,
    ): array {
        $class = $instance::class;

        if (!isset(self::$recipe[$class])) {
            self::$recipe[$class] = self::buildRecipe($class);
        }

        $recipe = self::$recipe[$class];

        $localBag = $session->get($sessionKey, []);
        $sharedBag = $session->get($sharedKey, []);

        $state = [];

        foreach ($recipe as $propName => $cfg) {
            if ($cfg['kind'] !== 'state') {
                continue;
            }

            $value = $cfg['reader']($instance);

            if (
                !is_scalar($value) &&
                !is_array($value) &&
                $value !== null
            ) {
                throw new \RuntimeException(
                    "Only scalar/array allowed for #[State] {$propName}",
                );
            }

            if ($cfg['shared']) {
                $sharedBag[$propName] = $value;
            } else {
                $localBag[$propName] = $value;
            }

            $state[$propName] = $value;
        }

        $session->set($sessionKey, $localBag);
        $session->set($sharedKey, $sharedBag);

        return $state;
    }

    private static function buildRecipe(string $class): array
    {
        $refl = new \ReflectionClass($class);
        $dummy = $refl->newInstanceWithoutConstructor();
        $recipe = [];

        foreach ($refl->getProperties() as $prop) {
            $name = $prop->getName();
            $prop->setAccessible(true);

            $reader = fn(object $o) => $prop->getValue($o);
            $writer = fn(object $o, $v) => $prop->setValue($o, $v);

            $hasState = false;
            $validate = null;

            $stateAttr = null;

            foreach ($prop->getAttributes() as $attr) {
                $type = $attr->getName();

                if ($type === State::class) {
                    $hasState = true;
                    $stateAttr = $attr->newInstance();
                }

                if ($type === Validate::class) {
                    $validate = $attr->newInstance();
                }
            }

            if (!$hasState) {
                continue;
            }

            $recipe[$name] = [
                'kind' => 'state',
                'shared' => $stateAttr?->shared ?? false,
                'reader' => $reader,
                'accessor' => $writer,
            ];

            if ($validate) {
                self::$validateRules[$class][$name] = explode('|', $validate->rules);

                $recipe[$name]['validate'] = [
                    'rules' => explode('|', $validate->rules),
                    'messages' => $validate->messages
                        ? json_decode($validate->messages, true)
                        : [],
                ];
            }
        }
        return $recipe;
    }

}
