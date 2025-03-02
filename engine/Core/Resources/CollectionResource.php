<?php

namespace Forge\Core\Resources;

use Forge\Core\Models\Model;
use Forge\Modules\ForgeOrm\Collection;
use Forge\Core\Resources\ModelResource;

class CollectionResource extends BaseResource
{
    /**
     * {@inheritDoc}
     *
     * Transform a Collection instance into an array of resources.
     *
     * @param mixed $resource
     * @return array<int, array<string, mixed>>
     *
     * @throws \InvalidArgumentException if $resource is not a Collection.
     */
    public function toArray(mixed $resource): array
    {
        if (!$resource instanceof Collection) {
            throw new \InvalidArgumentException('Resource must be an instance of Forge\Modules\ForgeOrm\Collection.');
        }

        return $resource->map(function ($item) {
            if ($item instanceof Model) {
                return (new ModelResource($item))->toArray($item);
            }
            return $item;
        })->toArray();
    }
}