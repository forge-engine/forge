<?php

namespace MyApp\Repositories;

use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeExplicitOrm\Exception\RepositoryException;
use Forge\Modules\ForgeExplicitOrm\Relationships\Relation;
use Forge\Modules\ForgeExplicitOrm\Repository\BaseRepository;
use MyApp\DataTransferObjects\SectionDTO;

class SectionRepository extends BaseRepository
{
    /**
     * @var class-string
     */
    protected string $dtoClass = SectionDTO::class;

    protected string $table = 'sections';

    /**
     * SectionRepository constructor
     *
     * @param DatabaseInterface $database
     * @throws RepositoryException
     */
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    /**
     * Find a section by its slug
     *
     * @param string $slug
     * @return ?object SectionDTO object or null if not found
     * @throws RepositoryException
     */
    public function findBySlug(string $slug): ?object
    {
        $sections = $this->where(['slug' => $slug]);

        return $sections[0] ?? null;
    }

    /**
     * Define the belongs-to relationship with CategoryRepository.
     *
     * @param string $relationName (Optional) Name of the relation, defaults to 'category'.
     * @return Relation
     */
    protected function belongsToCategory(string $relationName = 'category'): Relation
    {
        return new Relation(
            CategoryRepository::class,
            'category_id',
            'id',
            'id',
            $relationName,
            $this
        );
    }

    /**
     * Magic method to handle relationship calls as properties (e.g., $sectionRepository->category()).
     *
     * @param string $method
     * @param array $arguments
     * @return Relation
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $arguments)
    {
        if (str_starts_with($method, 'belongsTo')) {
            $relationName = lcfirst(substr($method, 9));
            if (method_exists($this, 'belongsTo' . ucfirst($relationName))) {
                return $this->{'belongsTo' . ucfirst($relationName)}($relationName);
            }
        }

        throw new \BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}