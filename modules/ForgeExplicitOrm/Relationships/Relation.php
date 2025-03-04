<?php

namespace Forge\Modules\ForgeExplicitOrm\Relationships;

use Forge\Modules\ForgeExplicitOrm\Repository\BaseRepository;

class Relation
{
    /**
     * @var string Fully qualified class name of the related relation
     * @phpstan-var class-string<BaseRepository>
     */
    protected string $relatedRepositoryClass;

    /**
     * @var string Foreign key column name
     */
    protected string $foreignKey;

    /**
     * @var string Local key column name (in the current table)
     */
    protected string $localKey;

    /**
     * @var string|null Owner key column name (in related table. for belongsTo relations). Null for hasOne/hasMany.
     */
    protected ?string $ownerKey;

    /**
     * @var string Name of the relation (example 'posts', 'sections', 'categories' etc)
     */
    protected string $relationName;

    /**
     * @var BaseRepository The repository instance on which the relation is defined
     */
    protected BaseRepository $parentRepository;

    /**
     * @var array<object>|object|null Cache for loaded related records to prevent repeated queries (per parent DTO instance)
     */
    protected mixed $relatedResults = null;
    
}