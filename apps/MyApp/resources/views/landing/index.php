<?php

use Forge\Core\Helpers\View;

/** @var array $data */
$links = ['links' => $data['links']];

?>

<div class="landing-wrapper">
    <div class="landing-container">
        <h1><p class="forge-logo">Forge</p></h1>
        <?php View::component('forge-links-list', $links) ?>
        <?php //View::component('stark.test', $links) ?>
    </div>
</div>