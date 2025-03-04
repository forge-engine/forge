<?php

namespace Forge\Modules\ForgeExplicitOrm\Relationships;

use Forge\Modules\ForgeExplicitOrm\Exception\RepositoryException;
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

    /**
     * Relation constructor
     *
     * @param string $relatedRepositoryClass
     * @param string $foreignKey
     * @param string $localKey
     * @param string|null $ownerKey
     * @param string $relationName
     * @param BaseRepository $parentRepository
     * @throws RepositoryException If the related repository class is not a subclass of BaseRepository
     */
    public function __construct(
        string         $relatedRepositoryClass,
        string         $foreignKey,
        string         $localKey,
        ?string        $ownerKey,
        string         $relationName,
        BaseRepository $parentRepository
    )
    {
        if (!is_subclass_of($relatedRepositoryClass, BaseRepository::class)) {
            throw new RepositoryException(
                sprintf(
                    'Related repository class %s must extend %s',
                    $relatedRepositoryClass,
                    BaseRepository::class
                ));
        }

        $this->relatedRepositoryClass = $relatedRepositoryClass;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->ownerKey = $ownerKey;
        $this->relationName = $relationName;
        $this->parentRepository = $parentRepository;
    }

    /**
     * @throws RepositoryException
     */
    public function for(object $parentDto): array
    {
        $parentDtoClass = get_class($parentDto);
        $parentTable = $this->parentRepository->getTable();
        $parentDtoId = $parentDto->{$this->localKey};

        /** @var BaseRepository $relatedRepository */
        $relatedRepository = new $this->relatedRepositoryClass($this->parentRepository->database);

        $relatedTable = $relatedRepository->getTable();
        $relatedDtoClass = $relatedRepository->getDtoClass();


        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :parent_id',
            $relatedTable,
            $this->foreignKey
        );
        $params = [':parent_id' => $parentDtoId];
        $relatedData = $this->parentRepository->database->query($sql, $params);

        $relatedDtos = [];
        foreach ($relatedData as $data) {
            $relatedDtos[] = $relatedRepository->createDtoFromData($data);
        }

        return $relatedDtos;
    }

    /**
     * Get the related repository class name
     *
     * @return string
     */
    public function getRelatedRepositoryClass(): string
    {
        return $this->relatedRepositoryClass;
    }

    /**
     * Get the foreign key column name.
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get the local key column name.
     *
     * @return string
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    /**
     * Get the owner key column name (can be null for hasOne/hasMany).
     *
     * @return string|null
     */
    public function getOwnerKey(): ?string
    {
        return $this->ownerKey;
    }

    /**
     * Get the relation name.
     *
     * @return string
     */
    public function getRelationName(): string
    {
        return $this->relationName;
    }

    /**
     * Get the parent repository instance.
     *
     * @return BaseRepository
     */
    public function getParentRepository(): BaseRepository
    {
        return $this->parentRepository;
    }

    /**
     * Load the related records lazy loading
     *
     * @param object $parentDto The DTO of the parent entity.
     * @return mixed Will return a DTO object for hasOne/belongsto or array<DTO> for hasMany
     * @throws RepositoryException
     */
    public function getRelated(object $parentDto): mixed
    {
        if ($this->relatedResults != null) {
            return $this->relatedResults;
        }

        $relatedRepositoryClass = $this->relatedRepositoryClass;
        $relatedRepository = new $relatedRepositoryClass($this->parentRepository->database);

        $localKeyValue = $parentDto->{$this->localKey};

        try {
            if ($this->ownerKey) {
                $relatedDto = $relatedRepository->find($parentDto->{$this->foreignKey});
                $this->relatedResults = $relatedDto;
                return $relatedDto;
            } else {
                $relatedDtos = $relatedRepository->where([$this->foreignKey => $localKeyValue]);
                $this->relatedResults = $relatedDtos;
                return $relatedDtos;
            }
        } catch (\Throwable $e) {
            throw new RepositoryException(
                sprintf('Error loading related records for relation %s: %s', $this->relationName, $e->getMessage()),
                0,
                $e
            );
        }
    }

}