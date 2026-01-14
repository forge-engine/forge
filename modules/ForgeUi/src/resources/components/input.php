<?php

use App\Modules\ForgeUi\DesignTokens;

$type = $type ?? 'text';
$variant = $error ? 'error' : ($variant ?? 'default');
$size = $size ?? 'md';
$disabled = $disabled ?? false;
$readonly = $readonly ?? false;
$name = $name ?? '';
$value = $value ?? '';
$placeholder = $placeholder ?? '';
$id = $id ?? ($name ? 'input-' . $name : 'input-' . uniqid());

$baseClasses = DesignTokens::input($variant, $size);
$classes = class_merge($baseClasses, $class ?? '');
?>
<div class="fw-input-wrapper">
    <?php if (isset($slots['label'])): ?>
        <label for="<?= e($id) ?>" class="block text-sm font-medium text-gray-700 mb-1">
            <?= $slots['label'] ?>
        </label>
    <?php endif; ?>
    <div class="relative">
        <?php if (isset($slots['prefix'])): ?>
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <?= $slots['prefix'] ?>
            </span>
        <?php endif; ?>
        <?php if (isset($slots['icon'])): ?>
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <?= $slots['icon'] ?>
            </span>
        <?php endif; ?>
        <input
            type="<?= e($type) ?>"
            id="<?= e($id) ?>"
            name="<?= e($name) ?>"
            value="<?= e($value) ?>"
            placeholder="<?= e($placeholder) ?>"
            <?= $disabled ? 'disabled' : '' ?>
            <?= $readonly ? 'readonly' : '' ?>
            class="<?= $classes ?> <?= (isset($slots['prefix']) || isset($slots['icon'])) ? 'pl-10' : '' ?> <?= (isset($slots['suffix']) || isset($slots['iconAfter'])) ? 'pr-10' : '' ?>"
        />
        <?php if (isset($slots['suffix'])): ?>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <?= $slots['suffix'] ?>
            </span>
        <?php endif; ?>
        <?php if (isset($slots['iconAfter'])): ?>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <?= $slots['iconAfter'] ?>
            </span>
        <?php endif; ?>
    </div>
    <?php if (isset($slots['helper']) && !isset($slots['error'])): ?>
        <p class="mt-1 text-sm text-gray-500">
            <?= $slots['helper'] ?>
        </p>
    <?php endif; ?>
    <?php if (isset($slots['error'])): ?>
        <p class="mt-1 text-sm text-red-600">
            <?= $slots['error'] ?>
        </p>
    <?php endif; ?>
</div>
