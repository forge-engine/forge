<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Repositories;

use App\Modules\ForgeStorage\Dto\TemporaryUrlDto;
use App\Modules\ForgeStorage\Models\TemporaryUrl;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class TemporaryUrlRepository
{
    public function __construct(protected QueryBuilderInterface $queryBuilder)
    {
        //parent::__construct($queryBuilder, TemporaryUrl::class, TemporaryUrlDto::class);
        
    }

    /** @return array<TemporaryUrlDto> */
    public function findAll(): array
    {
        //return parent::findAll();
        throw new \Exception('Not implemented');
    }

    public function find(int $limit, int $offset): array
    {
        return $this->queryBuilder
            ->select("*")
            ->limit($limit)
            ->offset($offset)
            ->orderBy('created_at', 'ASC')
            ->get(TemporaryUrlDto::class);
    }

    public function findByCleanPath(string $cleanPath): ?TemporaryUrlDto
    {
        return $this->queryBuilder
            ->select("*")
            ->where("clean_path", "=", $cleanPath)
            ->first(TemporaryUrlDto::class);
    }


    public function findById(mixed $id): ?TemporaryUrlDto
    {
        //return parent::findById($id);
        throw new \Exception('Not implemented');
    }

    public function create(array $data): int|false
    {
        //return parent::create($data);
        throw new \Exception('Not implemented');
    }

    public function update(mixed $id, array $data): int
    {
        //return parent::update($id, $data);
        throw new \Exception('Not implemented');
    }

    public function delete(mixed $id): int
    {
        //return parent::delete($id);
        throw new \Exception('Not implemented');
    }
}
