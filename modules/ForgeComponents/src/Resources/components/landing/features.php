<?php

$variant = $variant ?? 'default';
$columns = $columns ?? 3;
$features = $slots['features'] ?? $features ?? [];
$badgeText = $slots['badge'] ?? $badge ?? null;
$titleText = $slots['title'] ?? $title ?? null;
$subtitleText = $slots['subtitle'] ?? $subtitle ?? null;

$gridMap = [
  1 => 'grid-cols-1',
  2 => 'grid-cols-1 md:grid-cols-2',
  3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
  4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
];

$gridClass = $gridMap[$columns] ?? $gridMap[3];
$containerClasses = class_merge(['container', 'mx-auto', 'px-4', 'py-16'], $class ?? '');
?>
<section class="<?= $containerClasses ?>">
  <?php if ($titleText || $badgeText || $subtitleText): ?>
    <div class="text-center mb-12">
      <?php if ($badgeText): ?>
        <div class="mb-4">
          <?= component('ForgeUi:badge', [
            'variant' => $badgeVariant ?? 'primary',
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
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          <?= e($subtitleText) ?>
        </p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="grid <?= $gridClass ?> gap-6">
    <?php if (!empty($features) && is_array($features)): ?>
      <?php foreach ($features as $feature): ?>
        <?php if (is_array($feature)): ?>
          <?php
          $iconHtml = isset($feature['icon']) ? '<div class="mb-4 text-blue-600">' . $feature['icon'] . '</div>' : '';
          $badgeHtml = isset($feature['badge'])
            ? '<div class="mt-4">' . component('ForgeUi:badge', ['variant' => 'primary', 'children' => $feature['badge']], fromModule: true) . '</div>'
            : '';
          ?>
          <?= component('ForgeUi:card', [
            'variant' => $variant,
            'padding' => 'lg',
            'slots' => [
              'default' => $iconHtml . '
                                <h3 class="text-xl font-semibold mb-2">' . e($feature['title'] ?? '') . '</h3>
                                <p class="text-gray-600">' . e($feature['description'] ?? '') . '</p>
                                ' . $badgeHtml
            ]
          ], fromModule: true) ?>
        <?php else: ?>
          <?= $feature ?>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php elseif (isset($slots['default'])): ?>
      <?= $slots['default'] ?>
    <?php endif; ?>
  </div>
</section>
