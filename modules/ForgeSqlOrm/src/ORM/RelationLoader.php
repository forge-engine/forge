<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use App\Modules\ForgeSqlOrm\ORM\Values\RelationKind;
use ReflectionException;

final class RelationLoader
{
    /** @var Model[] */
    private array $parents;

    public function __construct(Model ...$parents)
    {
        $this->parents = $parents;
    }

    /**
     * @throws ReflectionException
     */
    public function load(string ...$relations): void
    {
        foreach ($relations as $relation) {
            $this->loadOne($relation);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function loadOne(string $relation): void
    {
        $rel = $this->parents[0]::describe($relation);
        $kind = $rel->kind;

        $localKeys = [];
        foreach ($this->parents as $p) {
            $localKeys[] = (string)$p->{$rel->localKey};
        }

        $localKeys = array_unique(array_filter($localKeys, fn($v) => $v !== null));
        if ($localKeys === []) {
            return;
        }

        /** @var Model $target */
        $target = $rel->target;
        $foreignColumn = $rel->foreignKey;

        $children = $target::query()
            ->whereIn($foreignColumn, $localKeys)
            ->get();

        $bucket = [];
        foreach ($children as $child) {
            $bucket[(string)$child->{$foreignColumn}][] = $child;
        }

        foreach ($this->parents as $parent) {
            $key = (string)$parent->{$rel->localKey};
            if ($kind === RelationKind::HasOne) {
                $parent->setRelation($relation, $bucket[$key][0] ?? null);
            } else {
                $parent->setRelation($relation, $bucket[$key] ?? []);
            }
        }
    }
}