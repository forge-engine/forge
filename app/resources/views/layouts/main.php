<?php
use Forge\Core\View\Component;

/**
    @var string $title
    @var string $content
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width" />
    <link rel="stylesheet" href="<?=asset(resource: 'css/style.css')?>" />
    <link rel="stylesheet" href="<?=asset(resource: 'css/custom.css')?>" />
    <title><?= $title ?? "Default Title" ?></title>
</head>

<body>
    <?= Component::render(name: "nav-bar", loadFromModule: false, props: [])?>
    <div class="main">
        <?= $content ?>
    </div>
    <?= Component::render(name: "footer", loadFromModule: false, props: []) ?>

    <script defer src="<?=asset(resource: 'js/htmx.min.js')?>" defer></script>
</body>

</html>