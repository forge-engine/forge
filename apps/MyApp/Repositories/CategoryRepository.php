<?php

namespace MyApp\Repositories;

use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeExplicitOrm\Exception\RepositoryException;
use Forge\Modules\ForgeExplicitOrm\Repository\BaseRepository;
use MyApp\DataTransferObjects\CategoryDTO;

class CategoryRepository extends BaseRepository
{
 /**
  * @var class-string
  */
 protected string $dtoClass = CategoryDTO::class;

 protected string $table = 'categories';

 /**
  *  CategoryRepository constructor.
  *
  * @param DatabaseInterface $database
  * @throws RepositoryException
  */
 public function __construct(DatabaseInterface $database)
 {
  parent::__construct($database);
 }

 /**
  * CategoryRepository constructor
  *
  * @param DatabaseInterface $databae
  * @throws RepositoryException
  */

 /**
  * Find a catebory by slug
  *
  * @param string $slug
  * @return ?CategoryDTO object or null if not found.
  * @throws RepositoryException
  */
 public function findBySlug(string $slug): ?CategoryDTO
 {
  $categories = $this->where(['slug' => $slug]);

  return $categories[0] ?? null;
 }
}