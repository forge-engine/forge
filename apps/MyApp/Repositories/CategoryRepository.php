<?php

namespace MyApp\Repositories;

use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeExplicitOrm\Exception\RepositoryException;
use Forge\Modules\ForgeExplicitOrm\Relationships\Relation;
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
  $categories = $this->whereCriteria(['slug' => $slug]);

  return $categories[0] ?? null;
 }

 /**
  * Define the has many relationship with SectionRepository
  *
  * @param string $relationName (optional) Name of the relation, defaults to 'sections'
  * @return Relation
  * @throws RepositoryException
  */
 protected function hasManySections(string $relationName = 'sections'): Relation
 {
  return new Relation(
   SectionRepository::class,
   'category_id',
   'id',
   null,
   $relationName,
   $this
  );
 }

 /**
  * Magic method to handle relationship call as properties, example $categoryRepository->sections()
  *
  * @param string $method
  * @param array $arguments
  * @return Relation
  * @throws \BadMethodCallException
  */
 /**
  * Magic method to handle relationship calls as properties (e.g., $categoryRepository->sections()).
  *
  * @param string $method
  * @param array $arguments
  * @return Relation
  * @throws \BadMethodCallException
  */
 public function __call(string $method, array $arguments)
 {
  $relationMethodName = 'hasMany' . ucfirst($method);

  if (method_exists($this, $relationMethodName)) {
   return $this->{$relationMethodName}();
  }

  throw new \BadMethodCallException(sprintf(
   'Call to undefined method %s::%s()', static::class, $method
  ));
 }

}