<?php

namespace Forge\Modules\ForgeOrm\Relations;

use Forge\Core\Models\Model;
use Forge\Modules\ForgeOrm\QueryBuilder;

class HasOne extends Relation
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
     * Constructor for HasOne relationship.
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
     * Get the result of the has-one relationship.
     *
     * @return Model|null
     */
    public function getResults(): ?Model
    {
        $relatedModelClass = $this->relatedModelClass;
        $relatedTable = $relatedModelClass::getTable();

        $result = $this->query
            ->table($relatedTable)
            ->where($this->foreignKey, '=', $this->parentModel->getAttribute($this->localKey))
            ->first();

        return $result ? new $relatedModelClass((array)$result) : null;
    }
}
