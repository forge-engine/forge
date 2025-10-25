<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use App\Modules\ForgeSqlOrm\ORM\Attributes\Hidden;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use BackedEnum;
use DateTimeImmutable;
use Exception;
use Forge\Core\Helpers\UUID;
use JsonException;
use ReflectionNamedType;
use RuntimeException;
use function App\Modules\ForgeSqlOrm\ORM\Values\cast;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Table;
use Forge\Core\DI\Attributes\Service;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

#[Service]
abstract class Model
{
    use CanLoadRelations;

    protected const string CONNECTION = 'default';
    protected const string SOFT_DELETE_COLUMN = 'deleted_at';

    private static array $reflections = [];

    private static array $tables = [];

    private static array $primaryProperties = [];

    private static ?string $softDeleteColumn = null;

    private static ?array $softDeleteColumnCache = null;

    private bool $exists = false;

    private array $original = [];

    final public static function fromRow(array $row): static
    {
        $instance = new static;
        $instance->exists = true;
        $instance->original = $row;

        foreach (static::reflection()->getProperties() as $p) {
            $col = $p->getAttributes(Column::class)[0] ?? null;
            if ($col === null) continue;

            $name = $p->getName();

            if ($p->getAttributes(Hidden::class) !== []) {
                $p->setValue($instance, null);
                continue;
            }

            if (!array_key_exists($name, $row)) continue;
            if (!property_exists($instance, $name)) continue;

            $reflectionType = $p->getType();

            $targetType = ($reflectionType instanceof ReflectionNamedType && !$reflectionType->isBuiltin())
                ? $reflectionType->getName()
                : null;

            $value = $col->newInstance()->cast
                ? cast($row[$name], $col->newInstance()->cast, $targetType)
                : $row[$name];

            $p->setValue($instance, $value);
        }

        return $instance;
    }

    final protected static function reflection(): ReflectionClass
    {
        return self::$reflections[static::class] ??= new ReflectionClass(static::class);
    }

    final public static function findBy(string $field, mixed $value): ?static
    {
        return static::query()->where(column: $field, operator: '=', value: $value)->first();
    }

    final public static function query(): ModelQuery
    {
        return new ModelQuery(static::class);
    }

    final public static function find(int|string $id): ?static
    {
        return static::query()->id($id)->first();
    }

    final public static function all(): array
    {
        return static::query()->get();
    }

    final public static function table(): string
    {
        return self::$tables[static::class] ??= static::reflection()
            ->getAttributes(Table::class)[0]
            ?->newInstance()
            ?->name ?? throw new LogicException('#[Table] missing on ' . static::class);
    }

    final public function save(): bool
    {
        return $this->exists ? $this->update() : $this->insert();
    }

    private function update(): bool
    {
        $dirty = $this->dirty();

        if ($dirty === []) {
            return true;
        }

        if (property_exists($this, 'updated_at')) {
            $updatedAtColumn = 'updated_at';
            $currentTime = new DateTimeImmutable();

            $this->{$updatedAtColumn} = $currentTime;

            $dirty[$updatedAtColumn] = $currentTime->format('Y-m-d H:i:s');
        }

        $pk = static::primaryProperty()->getName();
        $result = static::query()->id($this->{$pk})->update($dirty) > 0;

        if ($result) {
            $this->original = array_merge($this->original, $dirty);
        }

        return $result;
    }

    /**
     * @throws JsonException
     */
    private function dirty(): array
    {
        $dirty = [];
        foreach (self::reflection()->getProperties() as $p) {
            $colAttr = $p->getAttributes(Column::class)[0] ?? null;
            if ($colAttr === null) {
                continue;
            }

            $name = $p->getName();

            if (!$p->isInitialized($this)) {
                continue;
            }

            $curr = $p->getValue($this);
            $prev = $this->original[$name] ?? null;

            if ($curr !== $prev) {
                /** @var Column $col */
                $col = $colAttr->newInstance();
                $value = $curr;

                if ($curr instanceof BackedEnum) {
                    $value = $curr->value;
                } elseif ($col->cast === Cast::JSON) {
                    $value = json_encode($curr, JSON_THROW_ON_ERROR);
                } elseif ($curr instanceof DateTimeImmutable) {
                    $value = $curr->format('Y-m-d H:i:s');
                } elseif ($col->cast === Cast::BOOL && is_bool($curr)) {
                    $value = (int)$curr;
                }

                $dirty[$name] = $value;
            }
        }
        return $dirty;
    }

    public static final function primaryProperty(): ReflectionProperty
    {
        if (!isset(self::$primaryProperties[static::class])) {
            foreach (static::reflection()->getProperties() as $p) {
                if ($p->getAttributes(Column::class)[0]?->newInstance()->primary) {
                    self::$primaryProperties[static::class] = $p;
                    break;
                }
            }
            if (!isset(self::$primaryProperties[static::class])) {
                throw new LogicException('No primary column found on ' . static::class);
            }
        }
        return self::$primaryProperties[static::class];
    }

    private function insert(): bool
    {
        $data = $this->dirty();
        $pk = static::primaryProperty();
        $pkName = $pk->getName();

        if (self::isUuidPrimaryKey($pk) && empty($this->{$pkName})) {
            try {
                $newId = UUID::generate('uuid', ['version' => 4]);
            } catch (Exception $e) {
                throw new RuntimeException("Failed to generate UUID: " . $e->getMessage());
            }

            $this->{$pkName} = $newId;
            $data[$pkName] = $newId;
        }

        if (property_exists($this, 'created_at')) {
            $currentTime = new \DateTimeImmutable();
            $this->created_at = $currentTime;
            $data['created_at'] = $currentTime->format('Y-m-d H:i:s');
        }

        $success = static::query()->insert($data);

        if ($success === false) {
            return false;
        }

        if (is_int($success) && $success > 0) {
            $this->exists = true;
            $this->original = $data + [$pkName => $this->{$pkName}];
            return true;
        } elseif (is_int($success) && self::isUuidPrimaryKey($pk) === false) {
            $this->{$pkName} = $success;
            $this->exists = true;
            $this->original = $data + [$pkName => $success];
            return true;
        }

        if ($this->{$pkName} !== null) {
            $this->exists = true;
            $this->original = $data + [$pkName => $this->{$pkName}];
            return true;
        }

        return false;
    }

    private static function isUuidPrimaryKey(ReflectionProperty $pk): bool
    {
        $type = $pk->getType();
        return $type instanceof ReflectionNamedType && $type->getName() === 'string';
    }

    /**
     * @throws ReflectionException
     */
    final public function delete(): int
    {
        $pk = static::primaryProperty()->getName();
        $id = $this->{$pk};

        if ($id === null) {
            return 0;
        }

        $soft = static::softDeleteColumn() !== null;

        return $soft
            ? static::query()->id($id)->softDelete()
            : static::query()->id($id)->forceDelete();
    }

    /**
     * @throws ReflectionException
     */
    public static final function softDeleteColumn(): ?string
    {
        if (array_key_exists('softDeleteColumn', self::$softDeleteColumnCache ??= [])) {
            return self::$softDeleteColumnCache[static::class];
        }

        $col = self::reflection()->getProperty(self::SOFT_DELETE_COLUMN)->getName();
        return self::$softDeleteColumnCache[static::class] =
            self::reflection()->hasProperty(self::SOFT_DELETE_COLUMN) ? $col : null;
    }

    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        trigger_error('Undefined property: ' . static::class . '::$' . $name);
        return null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->relations[$name]) || isset($this->$name);
    }

    public function setRelation(string $name, mixed $value): void
    {
        $this->relations[$name] = $value;
    }

    public function toArray(): array
    {
        $out = [];
        foreach (self::reflection()->getProperties(ReflectionProperty::IS_PUBLIC) as $p) {
            if ($p->isStatic()) continue;
            $out[$p->getName()] = $p->getValue($this);
        }
        return $out;
    }
}