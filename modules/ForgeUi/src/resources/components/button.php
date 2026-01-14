<?php

use App\Modules\ForgeUi\DesignTokens;

$variant = $variant ?? 'primary';
$size = $size ?? 'md';
$disabled = $disabled ?? false;
$loading = $loading ?? false;
$fullWidth = $fullWidth ?? false;
$type = $type ?? 'button';

$baseClasses = DesignTokens::button($variant, $size);
$stateClasses = [];
if ($disabled || $loading) {
    $stateClasses = array_merge($stateClasses, DesignTokens::buttonState('disabled'));
}
if ($loading) {
    $stateClasses = array_merge($stateClasses, DesignTokens::buttonState('loading'));
}
if ($fullWidth) {
    $stateClasses[] = 'w-full';
}

$classes = class_merge($baseClasses, $stateClasses, $class ?? '');
?>
<button type="<?= e($type) ?>" <?= ($disabled || $loading) ? 'disabled' : '' ?> class="<?= $classes ?>">
    <?php if ($loading): ?>
        <span class="absolute inset-0 flex items-center justify-center">
            <?php
            $spinnerClasses = class_merge(DesignTokens::spinner('default', 'sm'), ['text-current']);
            ?>
            <span class="<?= $spinnerClasses ?>"></span>
        </span>
    <?php endif; ?>
    <?php if (isset($slots['icon'])): ?>
        <span class="fw-button-icon mr-2"><?= $slots['icon'] ?></span>
    <?php endif; ?>
    <span class="<?= $loading ? 'invisible' : '' ?>">
        <?= $slots['default'] ?? $children ?? '' ?>
    </span>
    <?php if (isset($slots['iconAfter'])): ?>
        <span class="fw-button-icon-after ml-2"><?= $slots['iconAfter'] ?></span>
    <?php endif; ?>
</button>
