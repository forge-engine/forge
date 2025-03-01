<?php

namespace Forge\Core\Models;

use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Date;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeOrm\Collection;
use Forge\Modules\ForgeOrm\QueryBuilder;
use Forge\Modules\ForgeOrm\Pagination\Paginator;
use Forge\Modules\ForgeOrm\Relations\Relation;
use Forge\Modules\ForgeOrm\Relations\HasMany;
use Forge\Modules\ForgeOrm\Relations\BelongsTo;

abstract class Model
{
    /**
     * Table name (if null, it will be guessed from class name).
     *
     * @var string|null
     */
    protected static ?string $table = null;

    /**
     * Primary key column name.
     *
     * @var string
     */
    protected static string $primaryKey = 'id';

    /**
     * Attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected array $fillable = [];

    /**
     * Attributes that are guarded from mass assignment.
     *
     * @var array<string>
     */
    protected array $guarded = ['id']; // Guard ID by default

    /**
     * Whether to use timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public bool $timestamps = true;

    /**
     * Model attributes (data).
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @var array<string, mixed>  Store original attribute values (after last save or fetch).
     */
    protected array $originalAttributes = [];

    /**
     * @var bool Indicate if the model exists in the database (after save or fetch).
     */
    public bool $exists = false;

    protected DatabaseInterface $db;
    protected QueryBuilder $queryBuilder;


    public function __construct(array $attributes = [])
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);
        $queryBuilder = $container->get(QueryBuilder::class);
        $this->db = $database;
        $this->queryBuilder = $queryBuilder;
        $this->fill($attributes);
    }

    /**
     * Mass assign attributes to the model.
     *
     * @param array<string, mixed> $attributes
     * @return void
     */
    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
    }

    /**
     * Check if attribute is fillable.
     *
     * @param string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        if (in_array($key, $this->guarded)) {
            return false; // Guarded attributes are not fillable
        }
        if (empty($this->fillable)) {
            return true; // If $fillable is empty, all are fillable except guarded
        }
        return in_array($key, $this->fillable); // Check if in $fillable array
    }

    /**
     * Set a model attribute.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get a model attribute.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Magic method to get attributes (e.g., $model->name).
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic method to set attributes (e.g., $model->name = 'John').
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, mixed $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Get the table name for the model.
     *
     * @return string
     */
    public static function getTable(): string
    {
        if (static::$table !== null) {
            return static::$table;
        }

        $classNameParts = explode('\\', static::class);
        $className = end($classNameParts);

        // Basic StudlyCase to snake_case conversion
        $tableName = strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $className));

        // Very basic pluralization (just adding 's' - might need more robust logic for real apps)
        $tableName .= 's';

        return $tableName;
    }

    /**
     * Get the primary key column name.
     *
     * @return string
     */
    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * Begin a new database query builder query on the table.
     *
     * @return QueryBuilder
     */
    protected static function query(): QueryBuilder
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);
        return (new QueryBuilder($database))->table(static::getTable());
    }

    /**
     * Find a model by its primary key.
     *
     * @param mixed $id
     * @return static|null
     */
    public static function find(mixed $id): ?static
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);

        $result = static::query()->where(static::getPrimaryKey(), $id)->first();
        if ($result) {
            $model = new static((array)$result, $database);
            $model->syncOriginal();
            return $model;
        }
        return null;
    }

    /**
     * Find a model by its key value.
     *
     * @param string $key
     * * @param string $value
     * @return static|null
     */
    public static function findBy(string $key, string $value): ?static
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);

        $result = static::query()->where($key, $value)->first();
        if ($result) {
            $model = new static((array)$result, $database);
            $model->syncOriginal();
            return $model;
        }
        return null;
    }

    /**
     * Get all models from the table.
     *
     * @return Collection
     */
    public static function all(): Collection
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);

        $results = static::query()->get();
        $models = [];
        foreach ($results as $result) {
            $model = new static((array)$result, $database);
            $model->syncOriginal();
            $models[] = $model;
        }
        return new Collection($models);
    }

    /**
     * Paginate the query results.
     *
     * @param int $perPage Number of items per page.
     * @param int|null $currentPage Current page number (optional, default is null which will be resolved to 1).
     * @return Paginator
     */
    public static function paginate(int $perPage = 15, ?int $currentPage = null): Paginator
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);

        $currentPage = $currentPage ?: Paginator::resolveCurrentPage();

        $total = static::query()->count();

        $results = static::query()
            ->limit($perPage)
            ->offset(($currentPage - 1) * $perPage)
            ->get();

        $models = [];
        foreach ($results as $result) {
            $model = new static((array)$result, $database);
            $model->syncOriginal();
            $models[] = $model;
        }

        return new Paginator(new Collection($models), $total, $perPage, $currentPage, []);
    }

    /**
     * Create a new model record in the database.
     *
     * @param array<string, mixed> $attributes
     * @return static|null
     */
    public static function create(array $attributes): ?static
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);

        $model = new static($attributes, $database);
        $model->save(); // Save to database
        if ($model->getAttribute(static::getPrimaryKey())) { // Check if primary key was set after save
            $model->syncOriginal(); // Track original attributes
            return $model;
        }
        return null;
    }

    /**
     * Save the model to the database (insert or update).
     *
     * @return bool
     */
    public function save(): bool
    {
        $attributes = $this->getDirtyAttributes();

        if (empty($attributes)) {
            return true;
        }

        if ($this->timestamps) {
            if (!isset($this->attributes['created_at']) && !$this->exists) {
                $this->setAttribute('created_at', Date::now('Y-m-d H:i:s'));
            }
            $this->setAttribute('updated_at', date('Y-m-d H:i:s'));
            $attributes['updated_at'] = $this->getAttribute('updated_at');
            if (!isset($attributes['created_at']) && isset($this->attributes['created_at'])) {
                $attributes['created_at'] = $this->getAttribute('created_at');
            }
        }

        $primaryKeyValue = $this->getAttribute(static::getPrimaryKey());

        if ($primaryKeyValue) {
            $updated = static::query()
                ->where(static::getPrimaryKey(), $primaryKeyValue)
                ->update($attributes);
            if ($updated > 0) {
                $this->syncOriginal();
                return true;
            }
            return false;
        } else {
            // Insert new record
            if ($this->timestamps && !isset($attributes['created_at'])) {
                $attributes['created_at'] = Date::now('Y-m-d H:i:s');
            }
            $insertId = $this->queryBuilder->insert($attributes);
            if ($insertId) {
                $this->setAttribute(static::getPrimaryKey(), $insertId);
                $this->syncOriginal();
                $this->exists = true;
                return true;
            }
            return false;
        }
    }

    /**
     * Update model attributes in the database.
     *
     * @param array<string, mixed> $attributes
     * @return bool
     */
    public function update(array $attributes): bool
    {
        if ($this->timestamps) {
            $attributes['updated_at'] = Date::now('Y-m-d H:i:s');
        }
        $primaryKeyValue = $this->getAttribute(static::getPrimaryKey());
        if (!$primaryKeyValue) {
            return false;
        }
        $updated = static::query()
            ->where(static::getPrimaryKey(), $primaryKeyValue)
            ->update($attributes);
        if ($updated > 0) {
            $this->syncOriginal();
            return true;
        }
        return false;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $primaryKeyValue = $this->getAttribute(static::getPrimaryKey());
        if (!$primaryKeyValue) {
            return false;
        }
        $deleted = static::query()
            ->where(static::getPrimaryKey(), $primaryKeyValue)
            ->delete();
        if ($deleted > 0) {
            $this->exists = false;
            return true;
        }
        return false;
    }

    /**
     * Get the underlying data prepared for JSON serialization.
     *
     * This method handles both single models and collections of models.
     * It can be overridden in concrete models for custom serialization.
     *
     * @return array<int, array<string, mixed>|array<string, mixed>>|array<string, mixed>
     */
    public function serializeForJson(): array
    {
        if ($this instanceof Collection) {
            return $this->map(function ($item) {
                if ($item instanceof Model) {
                    return $item->toArray();
                }

                return $item;
            })->toArray();
        } elseif ($this instanceof Model) {
            return $this->toArray();
        } else {
            return [];
        }
    }

    /**
     * Get the underlying attribute array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert the model to JSON.
     *
     * @param int $options JSON encoding options (optional).
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Magic method to convert the model to string (JSON representation).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Dynamically handle calls to the model for scopes, relationships, etc.
     * Example: User::whereName('John')->get(); User::posts(); // Relationship access
     *
     * @param string $method
     * @param array<int, mixed> $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);
        if (in_array(substr($method, 0, strlen('hasMany')), ['hasMany', 'belongsTo', 'hasOne', 'belongsToMany'])) {
            return (new static([], $database))->{$method}(...$parameters);
        }

        return static::query()->{$method}(...$parameters);
    }

    /**
     * Define a has-many relationship.
     *
     * @param string $relatedModelClass
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return HasMany
     */
    public function hasMany(string $relatedModelClass, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', static::class))) . '_' . static::getPrimaryKey();
        $localKey = $localKey ?: static::getPrimaryKey();

        return new HasMany(
            $this->queryBuilder->table(static::getTable()),
            $this,
            $relatedModelClass,
            $foreignKey,
            $localKey
        );
    }

    /**
     * Define a belongs-to relationship.
     *
     * @param string $relatedModelClass
     * @param string|null $foreignKey
     * @param string|null $ownerKey
     * @return BelongsTo
     */
    public function belongsTo(string $relatedModelClass, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', static::class))) . '_' . static::getPrimaryKey();
        $ownerKey = $ownerKey ?: 'id';

        return new BelongsTo(
            $this->queryBuilder->table(static::getTable()),
            $this,
            $relatedModelClass,
            $foreignKey,
            $ownerKey
        );
    }

    /**
     * Define a has-one relationship.
     *
     * @param string $relatedModelClass
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return HasOne
     */
    public function hasOne(string $relatedModelClass, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', static::class))) . '_' . static::getPrimaryKey();
        $localKey = $localKey ?: static::getPrimaryKey();

        return new HasOne(
            $this->queryBuilder->table(static::getTable()),
            $this,
            $relatedModelClass,
            $foreignKey,
            $localKey
        );
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param string $relatedModelClass
     * @param string $pivotTable The name of the pivot table.
     * @param string|null $foreignPivotKey The foreign key column in the pivot table for the current table.
     * @param string|null $relatedPivotKey The foreign key column in the pivot table for the related table.
     * @return BelongsToMany
     */
    public function belongsToMany(string $relatedModelClass, string $pivotTable, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): BelongsToMany
    {
        $foreignPivotKey = $foreignPivotKey ?: strtolower(basename(str_replace('\\', '/', static::class))) . '_' . static::getPrimaryKey();
        $relatedPivotKey = $relatedPivotKey ?: strtolower(basename(str_replace('\\', '/', $relatedModelClass))) . '_' . $relatedModelClass::getPrimaryKey();

        return new BelongsToMany(
            $this->queryBuilder->table(static::getTable()),
            $this,
            $relatedModelClass,
            $pivotTable,
            $foreignPivotKey,
            $relatedPivotKey
        );
    }

    /**
     * Get a relationship value from a method call (used by __get magic method).
     *
     * @param string $method
     * @return mixed
     */
    protected function getRelationFromMethod(string $method): mixed
    {
        $relation = $this->$method();

        if (!$relation instanceof Relation) {
            throw new \LogicException("Method {$method} must return a relationship instance.");
        }

        if (!isset($this->relations[$method])) {
            $this->relations[$method] = $relation->getResults();
        }

        return $this->relations[$method];
    }

    /**
     * Get the currently set relation results (eager loaded or already accessed).
     *
     * @param string $relationName
     * @return Collection|null
     */
    public function getRelation(string $relationName): ?Collection
    {
        return $this->relations[$relationName] ?? null;
    }


    /**
     * Check if a relationship is loaded.
     *
     * @param string $relationName
     * @return bool
     */
    public function relationLoaded(string $relationName): bool
    {
        return isset($this->relations[$relationName]);
    }

    /**
     * Eager load relationships for the model(s).
     *
     * @param array<string>|string $relations Array of relationship names or a single relationship name.
     * @return $this
     */
    public function load(array|string $relations): self
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        foreach ($relations as $relationName) {
            if (method_exists($this, $relationName)) {
                $relation = $this->$relationName();

                if (!$relation instanceof Relation) {
                    throw new \LogicException("Method {$relationName} must return a relationship instance.");
                }

                $this->relations[$relationName] = $relation->getResults();
            }
        }
        return $this;
    }

    /**
     * Sync original attributes - mark current attributes as "original".
     * Used after save, update, create to track changes.
     *
     * @return void
     */
    protected function syncOriginal(): void
    {
        $this->originalAttributes = $this->attributes;
        $this->exists = true;
    }

    /**
     * Get the attributes that have been changed since the last syncOriginal().
     *
     * @return array<string, mixed>
     */
    protected function getDirtyAttributes(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->originalAttributes) || $this->originalAttributes[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }


    /**
     * Magic method to dynamically handle calls for relationships (e.g., $model->posts()->where(...)->get()).
     *
     * @param string $method
     * @param array<int, mixed> $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this, $method) && ($relation = $this->$method()) instanceof Relation) {
            return $relation;
        }

        throw new \BadMethodCallException("Method {$method} does not exist on model " . static::class);
    }
}