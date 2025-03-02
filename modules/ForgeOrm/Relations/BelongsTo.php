<?php

namespace Forge\Modules\ForgeOrm\Relations;

use Forge\Core\Models\Model;
use Forge\Modules\ForgeOrm\Collection;
use Forge\Modules\ForgeOrm\QueryBuilder;

class BelongsTo extends Relation
{
    /**
     * @var string
     */
    protected string $foreignKey;

    /**
     * @var string
     */
    protected string $ownerKey;

    /**
     * Constructor for BelongsTo relationship.
     *
     * @param QueryBuilder $query
     * @param Model $parentModel
     * @param string $relatedModelClass
     * @param string $foreignKey
     * @param string $ownerKey
     */
    public function __construct(QueryBuilder $query, Model $parentModel, string $relatedModelClass, string $foreignKey, string $ownerKey)
    {
        parent::__construct($query, $parentModel, $relatedModelClass);
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }

    /**
     * Get the results of the belongs-to relationship.
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
            ->where($relatedTable . '.' . $this->ownerKey, '=', $this->parentModel->getAttribute($this->foreignKey))
            ->limit(1)
            ->get();
    }
}