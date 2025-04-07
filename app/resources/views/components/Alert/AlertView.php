<?php
use App\View\Components\Alert\AlertPropsDto;

/** @var AlertPropsDto $alert */
?>

<div class="alert alert--<?= $alert['type'] ?? "info" ?> mb-sm">
    <?= $alert['children'] ?>
</div>