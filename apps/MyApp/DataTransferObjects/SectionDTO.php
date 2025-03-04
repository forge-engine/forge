<?php

namespace MyApp\DataTransferObjects;

use Forge\Modules\ForgeExplicitOrm\DataTransferObjects\BaseDTO;

class SectionDTO extends BaseDTO
{
    public int $id;
    public int $category_id;
    public string $title;
    public string $slug;
    public ?string $content;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->category_id = (int)$data['category_id'];
        $this->slug = (string)$data['slug'];
        $this->content = (string)$data['content'];
    }
}