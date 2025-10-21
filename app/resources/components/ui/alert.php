<?php

use App\Components\Ui\AlertPropsDto;

/** @var AlertPropsDto $props */
?>
<div class="font-bold  <?= htmlspecialchars($props->type ?: 'info') ?>">
    <?= htmlspecialchars($props->children ?: 'info') ?? 'info' ?>
</div>