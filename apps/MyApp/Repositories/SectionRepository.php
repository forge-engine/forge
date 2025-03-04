<?php

namespace MyApp\Repositories;

use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeExplicitOrm\Exception\RepositoryException;
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
}