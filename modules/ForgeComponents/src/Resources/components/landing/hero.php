<?php

$variant = $variant ?? 'default';
$theme = $theme ?? 'light';
$size = $size ?? 'lg';

$badgeText = $slots['badge'] ?? $badge ?? null;
$titleText = $slots['title'] ?? $title ?? null;
$subtitleText = $slots['subtitle'] ?? $subtitle ?? null;
$primaryActionData = $slots['primaryAction'] ?? $primaryAction ?? null;
$secondaryActionData = $slots['secondaryAction'] ?? $secondaryAction ?? null;
$imageContent = $slots['image'] ?? $image ?? null;

$themeClasses = $theme === 'dark'
  ? ['bg-gray-900', 'text-white']
  : ['bg-white', 'text-gray-900'];

$containerClasses = class_merge(['container', 'mx-auto', 'px-4', 'py-16', 'md:py-24'], $themeClasses, $class ?? '');
?>
<section class="<?= $containerClasses ?>">
  <div class="max-w-4xl mx-auto text-center">
    <?php if ($badgeText): ?>
      <div class="mb-4">
        <?= component('ForgeUi:badge', [
          'variant' => $badgeVariant ?? 'primary',
          'size' => 'lg',
          'children' => $badgeText
        ], fromModule: true) ?>
      </div>
    <?php endif; ?>

    <?php if ($titleText): ?>
      <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
        <?= e($titleText) ?>
      </h1>
    <?php endif; ?>

    <?php if ($subtitleText): ?>
      <p class="text-xl md:text-2xl mb-8 text-gray-600 dark:text-gray-300">
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
          $primaryProps = is_array($primaryActionData)
            ? $primaryActionData
            : ['children' => $primaryActionData, 'variant' => 'primary', 'size' => $size];
          ?>
          <?= component('ForgeUi:button', $primaryProps, fromModule: true) ?>
        <?php endif; ?>

        <?php if ($secondaryActionData): ?>
          <?php
          $secondaryProps = is_array($secondaryActionData)
            ? $secondaryActionData
            : ['children' => $secondaryActionData, 'variant' => 'outline', 'size' => $size];
          ?>
          <?= component('ForgeUi:button', $secondaryProps, fromModule: true) ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($imageContent): ?>
      <div class="mt-12">
        <?= is_string($imageContent) && !str_starts_with($imageContent, '<')
          ? '<img src="' . e($imageContent) . '" alt="" class="rounded-lg shadow-2xl max-w-full h-auto">'
          : $imageContent ?>
      </div>
    <?php endif; ?>
  </div>
</section>
