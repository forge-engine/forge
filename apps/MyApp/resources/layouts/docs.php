<?php

use Forge\Core\Helpers\Path;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forge Documentation - The PHP Framework Where You Are In Control</title>
    <link rel="stylesheet" href="<?= Path::staticAssetUrl('css/error.css') ?>">
    <link rel="stylesheet" href="<?= Path::staticAssetUrl('css/style.css') ?>">
</head>
<body>
<div class="layout-wrapper">
    {{content}}
</div>
</body>
</html>