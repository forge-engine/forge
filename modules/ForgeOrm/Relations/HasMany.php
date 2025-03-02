<?php

namespace Forge\Modules\ForgeOrm\Relations;

use Forge\Core\Models\Model;
use Forge\Modules\ForgeOrm\Collection;
use Forge\Modules\ForgeOrm\QueryBuilder;

class HasMany extends Relation
{
    /**
     * @var string
     */
    protected string $foreignKey;

    /**
     * @var string
     */
    protected string $localKey;

    /**
     * Constructor for HasMany relationship.
     *
     * @param QueryBuilder $query
     * @param Model $parentModel
     * @param string $relatedModelClass
     * @param string $foreignKey
     * @param string $localKey
     */
    public function __construct(QueryBuilder $query, Model $parentModel, string $relatedModelClass, string $foreignKey, string $localKey)
    {
        parent::__construct($query, $parentModel, $relatedModelClass);
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * Get the results of the has-many relationship.
     *
     * @return Collection
     */
    public function getResults(): Collection
    {
        $relatedModelClass = $this->relatedModelClass;
        $relatedTable = $relatedModelClass::getTable();
        $relatedInstance = new $relatedModelClass();

        return $this->query
            ->table($relatedTable)
            ->where($this->foreignKey, '=', $this->parentModel->getAttribute($this->localKey))
            ->get();
    }
}