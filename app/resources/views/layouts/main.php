<?php
use Forge\Core\Helpers\ModuleResources;

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
    <link rel="stylesheet" href="/assets/app/css/style.css" />
    <link rel="stylesheet" href="/assets/app/css/custom.css" />
    <?= ModuleResources::loadStyles("forge-ui") ?>
    <title><?= $title ?? "Default Title" ?></title>
</head>

<body>

    <div class="main">
        <?= $content ?>
    </div>

    <script defer src="/assets/app/js/htmx.min.js" defer></script>
    <?= ModuleResources::loadScripts("forge-ui") ?>
</body>

</html>