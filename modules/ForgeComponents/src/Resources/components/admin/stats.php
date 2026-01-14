<?php

$columns = $columns ?? 4;
$stats = $slots['stats'] ?? $stats ?? [];

$gridMap = [
  1 => 'grid-cols-1',
  2 => 'grid-cols-1 md:grid-cols-2',
  3 => 'grid-cols-1 md:grid-cols-3',
  4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
];

$gridClass = $gridMap[$columns] ?? $gridMap[4];
$containerClasses = class_merge(['grid', $gridClass, 'gap-6'], $class ?? '');
?>
<div class="<?= $containerClasses ?>">
  <?php if (!empty($stats) && is_array($stats)): ?>
    <?php foreach ($stats as $stat): ?>
      <?php if (is_array($stat)): ?>
        <?php
        $trend = $stat['trend'] ?? null;
        $trendClass = $trend > 0 ? 'text-green-600' : ($trend < 0 ? 'text-red-600' : 'text-gray-600');
        $trendIcon = $trend > 0 ? '↑' : ($trend < 0 ? '↓' : '→');
        $trendDisplay = $trend !== null ? '<p class="text-sm ' . $trendClass . ' mt-1">' . $trendIcon . ' ' . abs($trend) . '%</p>' : '';
        $iconDisplay = isset($stat['icon']) ? '<div class="text-blue-600">' . $stat['icon'] . '</div>' : '';
        ?>
        <?= component('ForgeUi:card', [
          'variant' => 'default',
          'padding' => 'lg',
          'slots' => [
            'default' => '
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">' . e($stat['label'] ?? '') . '</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">' . e($stat['value'] ?? '0') . '</p>
                                    ' . $trendDisplay . '
                                </div>
                                ' . $iconDisplay . '
                            </div>
                        '
          ]
        ], fromModule: true) ?>
      <?php else: ?>
        <?= $stat ?>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php elseif (isset($slots['default'])): ?>
    <?= $slots['default'] ?>
  <?php endif; ?>
</div>
