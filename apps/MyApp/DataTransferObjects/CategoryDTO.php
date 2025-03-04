<?php

namespace MyApp\DataTransferObjects;

use Forge\Modules\ForgeExplicitOrm\DataTransferObjects\BaseDTO;

class CategoryDTO extends BaseDTO
{
    public int $id;
    public string $name;
    public string $slug;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->name = (string)$data['name'];
        $this->slug = (string)$data['slug'];
    }
}