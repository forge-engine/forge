<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\DTO;
use App\Modules\ForgeWire\Attributes\Model;
use App\Modules\ForgeWire\Attributes\Service;
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

        if (isset(self::$validateRules[$class])) {
            $toValidate = [];
            foreach (self::$validateRules[$class] as $prop => $rules) {
                if (array_key_exists($prop, $dirty)) {
                    $toValidate[$prop] = $dirty[$prop];
                }
            }
            if ($toValidate) {
                $validator = new Validator(
                    $toValidate,
                    self::$validateRules[$class],
                );
                $validator->validate();
            }
        }
        $stateBag = $session->get($sessionKey, []);
        $modelBag = $session->get($sessionKey . ":models", []);
        $dtoBag = $session->get($sessionKey . ":dtos", []);

        foreach ($recipe as $propName => $cfg) {
            $value = null;

            if ($cfg["kind"] === "state") {
                $value = $dirty[$propName] ?? ($stateBag[$propName] ?? null);
                $stateBag[$propName] = $value;
            } elseif ($cfg["kind"] === "model") {
                if (isset($modelBag[$propName])) {
                    [$modelClass, $idField, $id] = $modelBag[$propName];

                    /** @var \App\Modules\ForgeSqlOrm\ORM\Model $model */
                    $model = null;

                    if ($idField === $modelClass::getPrimaryKey()) {
                        $model = $modelClass::find($id);
                    } else {
                        $results = $modelClass::where($idField, $id);
                        $model = $results[0] ?? null;
                    }

                    $value = $model;
                }
            } elseif ($cfg["kind"] === "dto") {
                $data = [];
                if (isset($dtoBag[$propName])) {
                    [$storedClass, $storedData] = $dtoBag[$propName];
                    if ($storedClass === $cfg["class"]) {
                        $data = $storedData;
                    }
                }
                foreach ($dtoInput[$propName] ?? [] as $k => $v) {
                    $data[$k] = $v;
                }
                if ($data || !$cfg["initialized"]) {
                    $value = $cfg["fromArray"]
                        ? $cfg["class"]::fromArray($data)
                        : self::plainHydrateObject($cfg["class"], $data);
                }
            } elseif ($cfg["kind"] === "service") {
                $value = $this->container->make($cfg["class"]);
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
        $models = [];
        $dtos = [];

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
            } elseif ($cfg["kind"] === "model" && $value) {
                $models[$propName] = [
                    $cfg["repoClass"],
                    $cfg["idField"],
                    $value->{$cfg["idField"]} ?? null,
                ];
            } elseif ($cfg["kind"] === "dto" && $value) {
                $dtos[$propName] = [$cfg["class"], $value->toArray()];
            }
        }

        $session->set($sessionKey, $state);
        $session->set($sessionKey . ":models", $models);
        $session->set($sessionKey . ":dtos", $dtos);

        return $state;
    }

    private static function buildRecipe(string $class): array
    {
        $refl = new \ReflectionClass($class);
        $recipe = [];

        foreach ($refl->getProperties() as $prop) {
            $name = $prop->getName();
            $hasInit = $prop->isInitialized(new $class());
            $prop->setAccessible(true);

            $reader = fn(object $o) => $prop->getValue($o);
            $writer = fn(object $o, $v) => $prop->setValue($o, $v);

            foreach ($prop->getAttributes() as $attr) {
                $type = $attr->getName();

                if ($type === Validate::class) {
                    /** @var Validate $v */
                    $v = $attr->newInstance();
                    self::$validateRules[$class][$name] = explode(
                        "|",
                        $v->rules,
                    );
                    continue;
                }

                if ($type === State::class) {
                    $recipe[$name] = [
                        "kind" => "state",
                        "reader" => $reader,
                        "accessor" => $writer,
                    ];
                    continue 2;
                }

                if ($type === Model::class) {
                    $args = $attr->getArguments();
                    $repoClass =
                        $args["class"] ?? null ?: $prop->getType()?->getName();
                    $idField = $args["idField"] ?? "id";
                    $recipe[$name] = [
                        "kind" => "model",
                        "repoClass" => $repoClass . "Repository",
                        "idField" => $idField,
                        "reader" => $reader,
                        "accessor" => $writer,
                    ];
                    continue 2;
                }

                if ($type === DTO::class) {
                    $args = $attr->getArguments();
                    $dtoClass =
                        $args["class"] ?? null ?: $prop->getType()?->getName();
                    $fromArray = method_exists($dtoClass, "fromArray");
                    $recipe[$name] = [
                        "kind" => "dto",
                        "class" => $dtoClass,
                        "fromArray" => $fromArray,
                        "initialized" => $hasInit,
                        "reader" => $reader,
                        "accessor" => $writer,
                    ];
                    continue 2;
                }

                if ($type === Service::class) {
                    $args = $attr->getArguments();
                    $svcClass =
                        $args["class"] ?? null ?: $prop->getType()?->getName();
                    $recipe[$name] = [
                        "kind" => "service",
                        "class" => $svcClass,
                        "reader" => $reader,
                        "accessor" => $writer,
                    ];
                    continue 2;
                }
            }
        }

        return $recipe;
    }

    private static function plainHydrateObject(
        string $class,
        array $data,
    ): object {
        $obj = new $class();
        foreach ($data as $k => $v) {
            if (property_exists($obj, $k)) {
                $obj->$k = $v;
            }
        }
        return $obj;
    }
}
