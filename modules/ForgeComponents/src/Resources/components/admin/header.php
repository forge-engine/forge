<?php

$user = $user ?? null;
$notifications = $notifications ?? [];
$showMobileToggle = $showMobileToggle ?? true;
$titleText = $slots['title'] ?? $title ?? null;
$hasNotifications = !empty($notifications) || isset($slots['notifications']);
$notificationCount = count($notifications);

$headerClasses = class_merge(['flex', 'items-center', 'justify-between', 'px-4', 'md:px-6', 'py-4'], $class ?? '');
?>
<header class="<?= $headerClasses ?>">
    <div class="flex items-center">
        <?php if ($showMobileToggle): ?>
            <button type="button" data-sidebar-toggle class="mr-4 md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-label="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        <?php endif; ?>

        <?php if ($titleText): ?>
            <h1 class="text-xl md:text-2xl font-semibold text-gray-900">
                <?= e($titleText) ?>
            </h1>
        <?php endif; ?>

        <?php if (isset($slots['actions'])): ?>
            <div class="ml-4">
                <?= $slots['actions'] ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex items-center space-x-4">
        <?php if (isset($slots['search'])): ?>
            <?= $slots['search'] ?>
        <?php endif; ?>

        <?php if ($hasNotifications): ?>
            <?php
            $badgeHtml = $notificationCount > 0
                ? component('ForgeUi:badge', ['variant' => 'danger', 'size' => 'xs', 'children' => $notificationCount], fromModule: true)
                : '';
            ?>
            <?= component('ForgeUi:dropdown', [
                'id' => 'notifications-menu',
                'slots' => [
                    'trigger' => component('ForgeUi:button', [
                        'variant' => 'ghost',
                        'size' => 'sm',
                        'slots' => [
                            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>',
                            'default' => $badgeHtml
                        ]
                    ], fromModule: true),
                    'menu' => $slots['notifications'] ?? ''
                ]
            ], fromModule: true) ?>
        <?php endif; ?>

        <?php if ($user): ?>
            <?= component('ForgeUi:dropdown', [
                'id' => 'user-menu',
                'slots' => [
                    'trigger' => component('ForgeUi:avatar', [
                        'src' => $user['avatar'] ?? null,
                        'initials' => $user['initials'] ?? substr($user['name'] ?? 'U', 0, 2),
                        'size' => 'sm'
                    ], fromModule: true),
                    'menu' => $slots['userMenu'] ?? ''
                ]
            ], fromModule: true) ?>
        <?php endif; ?>
    </div>
</header>
