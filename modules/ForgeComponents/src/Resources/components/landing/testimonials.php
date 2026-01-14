<?php

$variant = $variant ?? 'default';
$columns = $columns ?? 3;
$testimonials = $slots['testimonials'] ?? $testimonials ?? [];
$badgeText = $slots['badge'] ?? $badge ?? null;
$titleText = $slots['title'] ?? $title ?? null;
$subtitleText = $slots['subtitle'] ?? $subtitle ?? null;

$gridMap = [
  1 => 'grid-cols-1',
  2 => 'grid-cols-1 md:grid-cols-2',
  3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
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
    <?php if (!empty($testimonials) && is_array($testimonials)): ?>
      <?php foreach ($testimonials as $testimonial): ?>
        <?php if (is_array($testimonial)): ?>
          <?php
          $avatarHtml = component('ForgeUi:avatar', [
            'src' => $testimonial['avatar'] ?? null,
            'initials' => $testimonial['initials'] ?? substr($testimonial['name'] ?? 'U', 0, 2),
            'size' => 'md'
          ], fromModule: true);
          ?>
          <?= component('ForgeUi:card', [
            'variant' => $variant,
            'padding' => 'lg',
            'slots' => [
              'default' => '
                                <div class="mb-4">
                                    <p class="text-gray-700 mb-4">"' . e($testimonial['quote'] ?? '') . '"</p>
                                </div>
                                <div class="flex items-center">
                                    ' . $avatarHtml . '
                                    <div class="ml-4">
                                        <div class="font-semibold">' . e($testimonial['name'] ?? '') . '</div>
                                        <div class="text-sm text-gray-600">' . e($testimonial['role'] ?? '') . '</div>
                                    </div>
                                </div>
                            '
            ]
          ], fromModule: true) ?>
        <?php else: ?>
          <?= $testimonial ?>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php elseif (isset($slots['default'])): ?>
      <?= $slots['default'] ?>
    <?php endif; ?>
  </div>
</section>
