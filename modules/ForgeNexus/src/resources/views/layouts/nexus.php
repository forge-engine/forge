<?php
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=env(key: 'APP_NAME', default:'Nexus CMS')?></title>
    <link rel="stylesheet" href="<?=module_asset(module:'forge-nexus', resource:'css/nexus.css')?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <?= component(name: 'nexus:sidebar', loadFromModule: true, props: []) ?>
        <!-- Main Content Area -->
        <main class="main-content">
            <?= component(name: 'nexus:header', loadFromModule: true, props: []) ?>
            <div class="dashboard-grid">
                <?=$content?>
            </div>
        </main>
    </div>
    <script defer src="<?=module_asset(module:'forge-nexus', resource:'js/nexus.js')?>"></script>
</body>

</html>