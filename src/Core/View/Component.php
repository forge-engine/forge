<?php

declare(strict_types=1);

namespace Forge\Core\View;

use Attribute;
use Forge\Core\DI\Container;

#[Attribute(Attribute::TARGET_CLASS)]
class Component
{
    public function __construct(public string $name) {}

    /**
     * @throws \ReflectionException
     */
    public static function render(string $name, array $props = []): string
    {
        $componentClass =
            "App\\View\\Components\\" . $name . "\\" . ucfirst($name);

        $componentPropsClassName =
            "App\\View\\Components\\" .
            $name .
            "\\" .
            ucfirst($name) .
            "PropsDto";

        $viewPath = "/app/views/components/{$name}/" . ucfirst($name) . "View";

        if (!class_exists($componentClass)) {
            throw new \RuntimeException(
                "Component class {$componentClass} not found: {$componentClass}"
            );
        }

        $reflection = new \ReflectionClass($componentClass);
        $component = null;
        $constructorParams = [];
        $hasDTO = false;

        if (class_exists($componentPropsClassName)) {
            $hasDTO = true;
            $propsDto = new $componentPropsClassName();
            foreach ($props as $key => $value) {
                if (property_exists($propsDto, $key)) {
                    $propsDto->$key = $value;
                }
            }
            $constructorParams[] = $propsDto;
        } else {
            $constructorParams[] = $props;
        }

        $constructor = $reflection->getConstructor();
        if ($constructor) {
            $resolvedParams = [];
            foreach ($constructor->getParameters() as $param) {
                if ($param->getName() === "props") {
                    $resolvedParams[] = $constructorParams[0];
                } else {
                    $paramType = $param->getType();
                    if ($paramType instanceof \ReflectionNamedType) {
                        if ($paramType && !$paramType->isBuiltin()) {
                            $resolvedParams[] = Container::getInstance()->make(
                                $paramType->getName()
                            );
                        }
                    }
                }
            }
            $component = $reflection->newInstanceArgs($resolvedParams);
        } else {
            $component = $reflection->newInstance();
        }

        ob_start();
        $renderResult = $component->render();

        if (is_string($renderResult)) {
            $content = $renderResult;
        } else {
            $content = ob_get_clean();
        }

        return $content;
    }
}
