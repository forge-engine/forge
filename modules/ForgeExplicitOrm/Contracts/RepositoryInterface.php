<?php

namespace Forge\Modules\ForgeExplicitOrm\Contracts;

interface RepositoryInterface
{
    /**
     * Find a record by ID.
     *
     * @param int|string $id
     *
     * @return ?object DTO object or null if not found
     */
    public function find(int|string $id): ?object;

    /**
     * Find all records.
     *
     * @return array<Object> Array of DTO objects
     */
    public function findAll(): array;
}