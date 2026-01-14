<?php

$variant = $variant ?? 'primary';
$theme = $theme ?? 'light';
$size = $size ?? 'lg';

$badgeText = $slots['badge'] ?? $badge ?? null;
$titleText = $slots['title'] ?? $title ?? null;
$subtitleText = $slots['subtitle'] ?? $subtitle ?? null;
$primaryActionData = $slots['primaryAction'] ?? $primaryAction ?? null;
$secondaryActionData = $slots['secondaryAction'] ?? $secondaryAction ?? null;
$alertText = $slots['alert'] ?? $alert ?? null;

$themeClasses = $theme === 'dark'
    ? ['bg-gray-900', 'text-white']
    : ['bg-blue-600', 'text-white'];

$containerClasses = class_merge(['container', 'mx-auto', 'px-4', 'py-16', 'rounded-lg'], $themeClasses, $class ?? '');
?>
<section class="<?= $containerClasses ?>">
    <div class="max-w-3xl mx-auto text-center">
        <?php if ($badgeText): ?>
            <div class="mb-4">
                <?= component('ForgeUi:badge', [
                    'variant' => 'neutral',
                    'size' => 'lg',
                    'children' => $badgeText
                ], fromModule: true) ?>
            </div>
        <?php endif; ?>

        <?php if ($titleText): ?>
            <h2 class="text-3xl md:text-4xl font-bold mb-4">
                <?= e($titleText) ?>
            </h2>
        <?php endif; ?>

        <?php if ($subtitleText): ?>
            <p class="text-xl mb-8 opacity-90">
                <?= e($subtitleText) ?>
            </p>
        <?php endif; ?>

        <?php if (isset($slots['default'])): ?>
            <div class="mb-8">
                <?= $slots['default'] ?>
            </div>
        <?php endif; ?>

        <?php if ($primaryActionData || $secondaryActionData): ?>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <?php if ($primaryActionData): ?>
                    <?php
                    $primaryVariant = $theme === 'dark' ? 'primary' : 'neutral';
                    $primaryProps = is_array($primaryActionData)
                        ? array_merge($primaryActionData, ['variant' => $primaryActionData['variant'] ?? $primaryVariant, 'size' => $primaryActionData['size'] ?? $size])
                        : ['children' => $primaryActionData, 'variant' => $primaryVariant, 'size' => $size];
                    ?>
                    <?= component('ForgeUi:button', $primaryProps, fromModule: true) ?>
                <?php endif; ?>

                <?php if ($secondaryActionData): ?>
                    <?php
                    $secondaryProps = is_array($secondaryActionData)
                        ? array_merge($secondaryActionData, ['variant' => 'outline', 'size' => $secondaryActionData['size'] ?? $size, 'class' => class_merge('border-white text-white hover:bg-white hover:text-blue-600', $secondaryActionData['class'] ?? '')])
                        : ['children' => $secondaryActionData, 'variant' => 'outline', 'size' => $size, 'class' => 'border-white text-white hover:bg-white hover:text-blue-600'];
                    ?>
                    <?= component('ForgeUi:button', $secondaryProps, fromModule: true) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($alertText): ?>
            <div class="mt-8">
                <?= component('ForgeUi:alert', [
                    'variant' => $alertVariant ?? 'info',
                    'children' => $alertText
                ], fromModule: true) ?>
            </div>
        <?php endif; ?>
    </div>
</section>
