<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Traits\InjectsAssets;

#[
    Module(
        name: "ForgeWire",
        description: "A lightway livewire like module for forge",
        order: 99,
        version: "1.0.6",
    ),
]
#[Service]
#[Compatibility(framework: ">=0.1.0", php: ">=8.3")]
#[Repository(type: "git", url: "https://github.com/forge-engine/modules")]
final class ForgeWireModule
{
    use OutputHelper;
    use InjectsAssets;

    public function register(Container $container): void
    {
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_REQUEST)]
    public function onAfterRequest(Request $request, Response $response): void
    {
        $this->registerWireAssets();
        $this->injectAssets($response);
    }

    private function registerWireAssets(): void
    {
        $assetHtml = '<script src="/assets/modules/forge-wire/js/forgewire.js" async></script>';
        $this->registerAsset(assetHtml: $assetHtml, beforeTag: '</body>');
    }
}
