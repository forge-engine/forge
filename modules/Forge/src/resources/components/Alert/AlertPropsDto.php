<?php

declare(strict_types=1);

namespace App\Modules\ForgeUi\Resources\Components\Alert;

class AlertPropsDto
{
    public function __construct(public string $type = '', public string $children = '')
    {
    }
}
