<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Attributes\Validate;
use Forge\Core\DI\Container;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Validation\Validator;

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
        ?bool $isMemorySession = null,
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

        $stateBag = $session->get($sessionKey, []);

        foreach ($recipe as $propName => $cfg) {
            $value = null;

            if ($cfg["kind"] === "state") {
                $value = $dirty[$propName] ?? ($stateBag[$propName] ?? null);
                $stateBag[$propName] = $value;
            }

            if ($value !== null || array_key_exists($propName, $dirty)) {
                $cfg["accessor"]($instance, $value);
            }
        }

        $session->set($sessionKey, $stateBag);
    }

    public function dehydrate(
        object $instance,
        SessionInterface $session,
        string $sessionKey,
    ): array {
        $class = $instance::class;
        if (!isset(self::$recipe[$class])) {
            self::$recipe[$class] = self::buildRecipe($class);
        }
        $recipe = self::$recipe[$class];

        $state = [];

        foreach ($recipe as $propName => $cfg) {
            $value = $cfg["reader"]($instance);

            if ($cfg["kind"] === "state") {
                if (
                    !is_scalar($value) &&
                    !is_array($value) &&
                    $value !== null
                ) {
                    throw new \RuntimeException(
                        "Only scalar/array allowed for #[State] {$propName}",
                    );
                }
                $state[$propName] = $value;
            }
        }

        $session->set($sessionKey, $state);

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

            foreach ($prop->getAttributes() as $attr) {
                $type = $attr->getName();

                if ($type === State::class) {
                    $hasState = true;
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
