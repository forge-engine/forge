<?php
use App\Modules\ForgeNexus\Resources\Components\Sidebar\ItemPropsDto;

/*** @var ItemPropsDto $data */
?>
<li class="nav-item <?=$data->isActive ? 'active' : '' ?>">
    <a href="/<?=$data->target?>" class="nav-link">
        <i class="fa-solid <?=$data->icon?>"></i>
        <span><?=$data->label?></span>
    </a>
</li>