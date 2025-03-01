<?php

namespace Forge\Core\Resources;

use Forge\Core\Contracts\Resources\ResourceInterface;

abstract class BaseResource implements ResourceInterface
{
    /**
     * @var mixed The resource being wrapped.
     */
    protected mixed $resource;

    /**
     * Constructor.
     *
     * @param mixed $resource The resource to be wrapped.
     */
    public function __construct(mixed $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get the wrapped resource.
     *
     * @return mixed
     */
    protected function getResource(): mixed
    {
        return $this->resource;
    }

    /**
     * {@inheritDoc}
     *
     * This base implementation simply returns an empty array.
     * Concrete resources should override this to provide actual transformation logic.
     *
     * @param mixed $resource
     * @return array<string, mixed>
     */
    public function toArray(mixed $resource): array
    {
        return []; // Base implementation returns empty array
    }
}