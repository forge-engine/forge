<?php
use App\Modules\ForgeUi\Resources\Components\Alert\AlertPropsDto;

/** @var AlertPropsDto $data */
?>
<div class="alert alert--<?= $data->type ?? '' ?> mb-sm">
    <?= $data->children ?? '' ?>
</div>