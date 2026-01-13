<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Traits;

use App\Modules\ForgeWire\Support\ForgeWireResponse;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

trait ReactiveControllerHelper
{
    protected ?string $__fw_id = null;

    public function isWireRequest(Request $request): bool
    {
        return $request->hasHeader('X-ForgeWire');
    }

    public function isReactive(): bool
    {
        $ref = new \ReflectionClass($this);
        return !empty($ref->getAttributes(\App\Modules\ForgeWire\Attributes\Reactive::class));
    }

    protected function getResponseContext(): ?ForgeWireResponse
    {
        $ref = new \ReflectionClass($this);
        $reactiveAttr = $ref->getAttributes(\App\Modules\ForgeWire\Attributes\Reactive::class);
        if (empty($reactiveAttr)) {
            return null;
        }

        $id = $this->getComponentId();
        if ($id === null) {
            return null;
        }

        return ForgeWireResponse::getContext($id);
    }

    protected function getComponentId(): ?string
    {
        return $this->__fw_id;
    }

    public function redirect(string $url, int $delay = 0): void
    {
        $context = $this->getResponseContext();
        if ($context === null) {
            throw new \RuntimeException('redirect() can only be called from within a ForgeWire action');
        }
        $context->setRedirect($url, $delay);
    }

    public function flash(string $type, string $message): void
    {
        $context = $this->getResponseContext();
        if ($context === null) {
            throw new \RuntimeException('flash() can only be called from within a ForgeWire action');
        }
        $context->addFlash($type, $message);
    }

    public function dispatch(string $event, array $data = []): void
    {
        $context = $this->getResponseContext();
        if ($context === null) {
            throw new \RuntimeException('dispatch() can only be called from within a ForgeWire action');
        }
        $context->addEvent($event, $data);
    }
}
