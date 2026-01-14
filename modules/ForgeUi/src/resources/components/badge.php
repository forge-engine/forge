<?php

use App\Modules\ForgeUi\DesignTokens;

$variant = $variant ?? 'primary';
$size = $size ?? 'md';
$rounded = $rounded ?? 'md';

$baseClasses = DesignTokens::badge($variant, $size);
$roundedClasses = [
    'none' => ['rounded-none'],
    'sm' => ['rounded'],
    'md' => ['rounded-md'],
    'lg' => ['rounded-lg'],
    'full' => ['rounded-full'],
];
$roundedClass = $roundedClasses[$rounded] ?? $roundedClasses['md'];

$classes = class_merge($baseClasses, $roundedClass, $class ?? '');
?>
<span class="<?= $classes ?>">
    <?php if (isset($slots['icon'])): ?>
        <span class="fw-badge-icon mr-1"><?= $slots['icon'] ?></span>
    <?php endif; ?>
    <?= $slots['default'] ?? $children ?? '' ?>
    <?php if (isset($slots['iconAfter'])): ?>
        <span class="fw-badge-icon-after ml-1"><?= $slots['iconAfter'] ?></span>
    <?php endif; ?>
</span>
