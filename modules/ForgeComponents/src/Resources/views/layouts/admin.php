<?php

use Forge\Core\Helpers\ModuleResources;

$sidebarItems = $sidebarItems ?? [];
$user = $user ?? null;
$title = $title ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <?= ModuleResources::loadStyles("forge-ui") ?>
    <?= raw(csrf_meta()) ?>
</head>
<body>
    <?= component('ForgeComponents:admin/layout', [
        'slots' => [
            'sidebar' => component('ForgeComponents:admin/sidebar', [
                'items' => $sidebarItems,
                'brand' => $brand ?? 'Admin',
                'logo' => $logo ?? null
            ], fromModule: true),
            'header' => component('ForgeComponents:admin/header', [
                'title' => $title,
                'user' => $user,
                'notifications' => $notifications ?? []
            ], fromModule: true),
            'breadcrumb' => isset($breadcrumb) ? component('ForgeComponents:admin/breadcrumb', ['items' => $breadcrumb], fromModule: true) : null,
            'default' => $content ?? ''
        ]
    ], fromModule: true) ?>

    <?= ModuleResources::loadScripts("forge-ui") ?>
</body>
</html>
