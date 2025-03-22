<?php

declare(strict_types=1);

namespace Forge\Core\Module;

enum LifecycleHookName: string
{
    case AFTER_BOOT = 'afterBoot';
    case AFTER_MODULE_LOAD = 'afterModuleLoad';
    case AFTER_MODULE_REGISTER = 'afterModuleRegister';
    case AFTER_CONFIG_LOADED = 'afterConfigLoaded';
    case APP_BOOTED = 'appBooted';
}
