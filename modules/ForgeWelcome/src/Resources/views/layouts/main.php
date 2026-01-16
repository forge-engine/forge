<?php
/**
    @var string $title
    @var string $content
 */

 use Forge\Core\Helpers\ModuleResources;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width" />
    <link rel="stylesheet" href="<?= ModuleResources::pathTo(module: 'forge-welcome', resource: 'css/style.css')?>" />
    <link rel="stylesheet" href="<?= ModuleResources::pathTo(module: 'forge-welcome', resource: 'css/custom.css')?>" />
    <title><?= $title ?? "Forge Starter" ?></title>
</head>

<body>
    <div class="main">
        <?= $content ?>
    </div>
    <?= ModuleResources::loadScripts('forge-ui') ?>
</body>

</html>