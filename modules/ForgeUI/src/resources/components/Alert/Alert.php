<?php

declare(strict_types=1);

namespace App\Modules\ForgeUi\Resources\Components\Alert;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;
use App\Modules\ForgeUi\Resources\Components\Alert\AlertPropsDto;

#[Component(name: "forge-ui:alert", useDto: true)]
class Alert extends BaseComponent
{
    public function __construct(AlertPropsDto $props)
    {
        parent::__construct($props);
    }

    public function render(): mixed
    {
        $data = [
            "alert" => $this->props,
        ];
        return $this->renderview(viewPath: "Alert/AlertView", data: $data, loadFromModule: true);
    }
}
