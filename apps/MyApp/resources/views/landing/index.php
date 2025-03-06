<?php

use Forge\Core\Helpers\View;

/** @var array $data */
$links = ['links' => $data['links']];


$queue = new \Forge\Modules\ForgeQueue\Queue(\Forge\Core\Helpers\App::db());

$queue->dispatch(new \MyApp\Jobs\EmailJob('jeremias2@gmail.com', 'This is a test', 'Some content'));

?>

<div class="landing-wrapper">
    <div class="landing-container">
        <h1><p class="forge-logo">Forge</p></h1>
        <?php View::component('forge-links-list', $links) ?>
        <?php //View::component('stark.test', $links) ?>
    </div>
</div>