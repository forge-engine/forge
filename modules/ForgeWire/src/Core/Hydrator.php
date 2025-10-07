<?php

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\Validate;
use Forge\Core\DI\Container;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Validation\Validator;
use ReflectionAttribute;
use ReflectionClass;

final class Hydrator
{
    private const A_STATE = 'App\\Modules\\ForgeWire\\Attributes\\State';
    private const A_MODEL = 'App\\Modules\\ForgeWire\\Attributes\\Model';
    private const A_DTO = 'App\\Modules\\ForgeWire\\Attributes\\DTO';
    private const A_SERVICE = 'App\\Modules\\ForgeWire\\Attributes\\Service';
    private const A_VALIDATE = 'App\\Modules\\ForgeWire\\Attributes\\Validate';

    public function __construct(private Container $container)
    {
    }

    public function hydrate(object $instance, array $dirty, SessionInterface $session, string $sessionKey): void
    {
        $ref = new ReflectionClass($instance);

        $dirtyGroups = [];
        foreach ($dirty as $k => $v) {
            $pos = strpos($k, '.');
            if ($pos !== false) {
                $prefix = substr($k, 0, $pos);
                $field = substr($k, $pos + 1);
                $dirtyGroups[$prefix][$field] = $v;
            }
        }

        $validationRules = [];
        $validationData = [];

        foreach ($ref->getProperties() as $prop) {
            $name = $prop->getName();

            $validateAttr = $prop->getAttributes(self::A_VALIDATE, \ReflectionAttribute::IS_INSTANCEOF);

            if (!empty($validateAttr)) {
                /** @var Validate $validateInstance */
                $validateInstance = $validateAttr[0]->newInstance();
                if (array_key_exists($name, $dirty)) {
                    $validationRules[$name] = explode('|', $validateInstance->rules);
                    $validationData[$name] = $dirty[$name];
                }
            }

            if (!empty($validationRules)) {
                $validator = new Validator($validationData, $validationRules);
                $validator->validate();
            }

            foreach ($prop->getAttributes() as $attr) {
                $type = $attr->getName();

                if ($type === self::A_VALIDATE) {
                    continue;
                }

                if ($type === self::A_STATE) {
                    $state = $session->get($sessionKey, []);
                    if (array_key_exists($name, $dirty)) {
                        $state[$name] = $dirty[$name];
                    }
                    if (array_key_exists($name, $state)) {
                        $prop->setAccessible(true);
                        $prop->setValue($instance, $state[$name]);
                    }
                    continue;
                }

                if ($type === self::A_MODEL) {
                    $bag = $session->get($sessionKey . ':models', []);
                    if (isset($bag[$name])) {
                        [$class, $idField, $id] = $bag[$name];
                        $repo = $this->container->make($class . 'Repository');
                        $prop->setAccessible(true);
                        $prop->setValue($instance, $repo->findByField($idField, $id));
                    }
                    continue;
                }

                if ($type === self::A_DTO) {
                    $args = $this->args($attr);
                    $class = $args['class'] ?? $prop->getType()?->getName();
                    if (!$class) {
                        continue;
                    }

                    $bag = $session->get($sessionKey . ':dtos', []);
                    $data = [];
                    if (isset($bag[$name])) {
                        [$cls, $stored] = $bag[$name];
                        if ($cls === $class) {
                            $data = $stored;
                        }
                    }

                    if (isset($dirtyGroups[$name])) {
                        foreach ($dirtyGroups[$name] as $field => $val) {
                            $data[$field] = $val;
                        }
                    }

                    if (!$data && $prop->isInitialized($instance)) {
                        continue;
                    }

                    if (method_exists($class, 'fromArray')) {
                        $obj = $class::fromArray($data);
                    } else {
                        $obj = new $class();
                        foreach ($data as $k => $v) {
                            if (property_exists($obj, $k)) {
                                $obj->$k = $v;
                            }
                        }
                    }

                    $prop->setAccessible(true);
                    $prop->setValue($instance, $obj);
                    continue;
                }

                if ($type === self::A_SERVICE) {
                    $args = $this->args($attr);
                    $class = $args['class'] ?? $prop->getType()?->getName();
                    if ($class) {
                        $prop->setAccessible(true);
                        $prop->setValue($instance, $this->container->make($class));
                    }
                    continue;
                }
            }
        }
    }

    public function dehydrate(object $instance, SessionInterface $session, string $sessionKey): array
    {
        $state = [];
        $models = [];
        $dtos = [];

        $ref = new ReflectionClass($instance);
        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($instance);

            foreach ($prop->getAttributes() as $attr) {
                $type = $attr->getName();

                if ($type === self::A_STATE) {
                    if (!is_scalar($value) && !is_array($value) && !is_null($value)) {
                        throw new \RuntimeException("Only scalar/array allowed for #[State] {$prop->getName()}");
                    }
                    $state[$prop->getName()] = $value;
                    continue;
                }

                if ($type === self::A_MODEL && $value) {
                    $args = $this->args($attr);
                    $class = $args['class'] ?? null;
                    $idField = $args['idField'] ?? 'id';
                    $models[$prop->getName()] = [$class, $idField, $value->{$idField} ?? null];
                    continue;
                }

                if ($type === self::A_DTO && $value) {
                    $args = $this->args($attr);
                    $class = $args['class'] ?? null;
                    $dtos[$prop->getName()] = [$class, $value->toArray()];
                    continue;
                }
            }
        }

        $session->set($sessionKey, $state);
        $session->set($sessionKey . ':models', $models);
        $session->set($sessionKey . ':dtos', $dtos);

        return $state;
    }

    private function args(ReflectionAttribute $attr): array
    {
        $a = $attr->getArguments();
        if (!array_is_list($a)) {
            return $a;
        }
        $out = [];
        if (isset($a[0])) {
            $out['class'] = $a[0];
        }
        if (isset($a[1])) {
            $out['idField'] = $a[1];
        }
        return $out;
    }
}
