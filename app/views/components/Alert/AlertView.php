<?php
use App\View\Components\Alert\AlertPropsDto;

/** @var AlertPropsDto $alert */
?>

<div class="alert alert--<?= $alert->type ?? "info" ?>">
    <strong><?= $alert->type ?? "Alert" ?></strong>
    <?= $alert->children ?>
</div>