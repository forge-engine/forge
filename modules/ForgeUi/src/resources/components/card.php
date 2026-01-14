<?php

use App\Modules\ForgeUi\DesignTokens;

$variant = $variant ?? 'default';
$padding = $padding ?? 'md';

$baseClasses = DesignTokens::card($variant);
$paddingClasses = [
    'none' => [],
    'sm' => ['p-3'],
    'md' => ['p-4'],
    'lg' => ['p-6'],
    'xl' => ['p-8'],
];
$paddingClass = $paddingClasses[$padding] ?? $paddingClasses['md'];

$classes = class_merge($baseClasses, $paddingClass, $class ?? '');
?>
<div class="<?= $classes ?>">
    <?php if (isset($slots['image'])): ?>
        <div class="fw-card-image">
            <?= $slots['image'] ?>
        </div>
    <?php endif; ?>
    <?php if (isset($slots['header'])): ?>
        <div class="fw-card-header border-b border-gray-200 pb-3 mb-3">
            <?= $slots['header'] ?>
        </div>
    <?php endif; ?>
    <div class="fw-card-body">
        <?= $slots['default'] ?? $children ?? '' ?>
    </div>
    <?php if (isset($slots['footer'])): ?>
        <div class="fw-card-footer border-t border-gray-200 pt-3 mt-3">
            <?= $slots['footer'] ?>
        </div>
    <?php endif; ?>
</div>
