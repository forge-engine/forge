<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeHub\Contracts\ForgeHubInterface;
use App\Modules\ForgeHub\Services\ForgeHubService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\HubItem;
use Forge\Core\Module\ForgeIcon;
use Forge\Core\Security\PermissionsEnum;

#[Module(name: 'ForgeHub', description: 'Administration Hub for Forge Framework', order: 1)]
#[HubItem(label: 'CLI Command', route: '/hub/commands', icon: ForgeIcon::COG, order: 1, permissions: [PermissionsEnum::RUN_COMMAND, PermissionsEnum::VIEW_COMMAND])]
#[HubItem(label: 'Logs', route: '/hub/logs', icon: ForgeIcon::LOG)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeHubModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $container->bind(ForgeHubInterface::class, ForgeHubService::class);
        $this->registerNexusItems($container);
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
    }

    private function registerNexusItems(Container $container): void
    {
        $menuFile = BASE_PATH . '/modules/ForgeHub/config/menu.php';

        if (!class_exists(\App\Modules\ForgeHub\ForgeHubModule::class)) {
            return;
        }

        $existingEntries = file_exists($menuFile) ? include $menuFile : [];

        $menuEntries = [];
        $hasChanges = false;

        foreach ($container->getServiceIds() as $serviceId) {
            try {
                $reflection = new \ReflectionClass($serviceId);
            } catch (\ReflectionException $e) {
                continue;
            }

            foreach ($reflection->getAttributes(NexusItem::class) as $attribute) {
                /** @var NexusItem $instance */
                $instance = $attribute->newInstance();
                $newEntry = [
                    'label'       => $instance->label,
                    'route'       => $instance->route,
                    'icon'        => $instance->icon?->value ?? null,
                    'order'       => $instance->order,
                    'permissions' => array_map(fn ($perm) => $perm->value, $instance->permissions),
                ];

                // Check if this entry already exists
                $existingEntryKey = array_search($newEntry['route'], array_column($existingEntries, 'route'));

                if ($existingEntryKey !== false) {
                    $existingEntry = $existingEntries[$existingEntryKey];

                    // Check if anything changed
                    if ($existingEntry !== $newEntry) {
                        $hasChanges = true;
                        $existingEntries[$existingEntryKey] = $newEntry;
                    }

                    $menuEntries[] = $existingEntries[$existingEntryKey];
                } else {
                    // New entry found, mark changes
                    $hasChanges = true;
                    $menuEntries[] = $newEntry;
                }
            }
        }

        // Sort by order
        usort($menuEntries, fn ($a, $b) => $a['order'] <=> $b['order']);

        // Only write if there were changes
        if ($hasChanges) {
            $output = "<?php\n\nreturn [\n";
            foreach ($menuEntries as $entry) {
                $output .= "    [\n";
                foreach ($entry as $key => $value) {
                    $output .= "        '{$key}' => ";
                    if (is_array($value)) {
                        $output .= "[\n";
                        foreach ($value as $item) {
                            $output .= "            '{$item}',\n";
                        }
                        $output .= "        ],\n";
                    } elseif (is_string($value)) {
                        $output .= "'{$value}',\n";
                    } elseif (is_null($value)) {
                        $output .= "null,\n";
                    } else {
                        $output .= var_export($value, true) . ",\n";
                    }
                }
                $output .= "    ],\n";
            }
            $output .= "];\n";

            file_put_contents($menuFile, $output);
        }
    }
}
