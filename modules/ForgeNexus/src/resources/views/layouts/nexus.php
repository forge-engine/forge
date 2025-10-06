<?php

use Forge\Core\Helpers\ModuleResources;
use Forge\Core\View\Component;

/** @var string $content */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus CMS</title>
    <link rel="stylesheet" href="<?= ModuleResources::pathTo(module: 'forge-nexus', resource: 'css/main.css') ?>">
    <link rel="stylesheet" href="<?= ModuleResources::pathTo(module: 'forge-nexus', resource: 'css/nexus.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <?= Component::render(name: 'nexus:sidebar', loadFromModule: true) ?>
        <!-- Main Content Area -->
        <main class="main-content">
            <?= Component::render(name: 'nexus:header', loadFromModule: true) ?>
            <div class="dashboard-grid">
                <?= $content ?>
            </div>
        </main>
    </div>

    <?= forgewire() ?>
    <script defer src="<?= ModuleResources::pathTo(module: 'forge-nexus', resource: 'js/nexus.js') ?>"></script>
</body>

</html>