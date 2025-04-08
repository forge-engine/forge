<?php

declare(strict_types=1);

namespace App\Modules\ForgeUi\Resources\Components\Alert;

class AlertPropsDto
{
    public string $type = '';
    public string $children = '';

    public function __construct(string $type = '', string $children = '')
    {
        $this->type = $type;
        $this->children = $children;
    }
}
