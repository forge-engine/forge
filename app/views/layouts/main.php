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
    <link rel="stylesheet" href="/assets/css/style.css" />
    <link rel="stylesheet" href="/assets/css/custom.css" />
    <title><?= $title ?? "Default Title" ?></title>
</head>

<body>
    <?= Component::render("navbar") ?>
    <div class="main">
        <?= $content ?>
    </div>
    <?= Component::render("footer", ["year" => date("Y")]) ?>
</body>

</html>