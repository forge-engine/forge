<?php
use App\Modules\ForgeUi\Resources\Components\Alert\AlertPropsDto;

/** @var AlertPropsDto $data */
?>
<div class="alert alert--<?= $data->type ?? "info" ?> mb-sm">
    <?= $data->children ?>
</div>