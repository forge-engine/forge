<?php

namespace Forge\Core\Contracts\Resources;

interface ResourceInterface
{
    /**
     * Transform the given data into an array suitable for API response.
     *
     * @param mixed $resource The resource to be transformed (e.g., Model, Collection, array).
     * @return array<string, mixed>
     */
    public function toArray(mixed $resource): array;
}