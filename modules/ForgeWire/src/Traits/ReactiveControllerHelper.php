<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Traits;

use Forge\Core\Http\Request;

trait ReactiveControllerHelper
{
    public function isWireRequest(Request $request): bool
    {
        return $request->hasHeader('X-ForgeWire');
    }

    public function isReactive(): bool
    {
        $ref = new \ReflectionClass($this);
        return !empty($ref->getAttributes(\App\Modules\ForgeWire\Attributes\Reactive::class));
    }
}
