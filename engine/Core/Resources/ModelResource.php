<?php

namespace Forge\Core\Resources;

use Forge\Core\Models\Model;

class ModelResource extends BaseResource
{
    /**
     * {@inheritDoc}
     *
     * Transform a Model instance into an array.
     *
     * @param mixed $resource
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException if $resource is not a Model.
     */
    public function toArray(mixed $resource): array
    {
        if (!$resource instanceof Model) {
            throw new \InvalidArgumentException('Resource must be an instance of Forge\Modules\ForgeOrm\Model.');
        }

        return $resource->toArray();
    }
}