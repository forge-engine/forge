<?php

$variant = $variant ?? 'default';
$padding = $padding ?? 'md';
$hoverable = $hoverable ?? false;

$cardClasses = class_merge([], $hoverable ? ['hover:shadow-lg', 'transition-shadow'] : [], $class ?? '');
?>
<?= component('ForgeUi:card', [
    'variant' => $variant,
    'padding' => $padding,
    'class' => $cardClasses,
    'slots' => [
        'header' => isset($slots['header']) || isset($title) ? ($slots['header'] ?? '<h3 class="text-lg font-semibold text-gray-900">' . e($title ?? '') . '</h3>') : null,
        'default' => $slots['default'] ?? $content ?? '',
        'footer' => $slots['footer'] ?? null
    ]
], fromModule: true) ?>
