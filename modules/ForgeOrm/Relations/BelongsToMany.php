<?php

namespace Forge\Modules\ForgeOrm\Relations;

use Forge\Core\Models\Model;
use Forge\Modules\ForgeOrm\Collection;
use Forge\Modules\ForgeOrm\QueryBuilder;

class BelongsToMany extends Relation
{
    /**
     * @var string
     */
    protected string $pivotTable;

    /**
     * @var string
     */
    protected string $foreignPivotKey;

    /**
     * @var string
     */
    protected string $relatedPivotKey;

    /**
     * Constructor for BelongsToMany relationship.
     *
     * @param QueryBuilder $query
     * @param Model $parentModel
     * @param string $relatedModelClass
     * @param string $pivotTable
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     */
    public function __construct(QueryBuilder $query, Model $parentModel, string $relatedModelClass, string $pivotTable, string $foreignPivotKey, string $relatedPivotKey)
    {
        parent::__construct($query, $parentModel, $relatedModelClass);
        $this->pivotTable = $pivotTable;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
    }

    /**
     * Get the results of the many-to-many relationship.
     *
     * @return Collection
     */
    public function getResults(): Collection
    {
        $relatedModelClass = $this->relatedModelClass;
        $relatedTable = $relatedModelClass::getTable();
        $relatedInstance = new $relatedModelClass();

        $results = $this->query
            ->table($this->pivotTable)
            ->join($relatedTable, $this->pivotTable . '.' . $this->relatedPivotKey, '=', $relatedTable . '.' . $relatedInstance->getPrimaryKey())
            ->where($this->pivotTable . '.' . $this->foreignPivotKey, '=', $this->parentModel->getAttribute($this->parentModel->getPrimaryKey()))
            ->get();

        $relatedModels = [];
        foreach ($results as $result) {
            $relatedModels[] = new $relatedModelClass((array)$result);
        }

        return new Collection($relatedModels);
    }
}
