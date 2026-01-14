<?php

$actions = $actions ?? [];
$columns = $columns ?? 2;

$gridClasses = [
    1 => 'grid-cols-1',
    2 => 'grid-cols-2',
    3 => 'grid-cols-3',
    4 => 'grid-cols-4',
];

$containerClasses = class_merge(['grid', $gridClasses[$columns] ?? $gridClasses[2], 'gap-4'], $class ?? '');
?>
<div class="<?= $containerClasses ?>">
    <?php if (empty($actions)): ?>
        <p class="text-sm text-gray-500 text-center py-4 col-span-full">No actions available</p>
    <?php else: ?>
        <?php foreach ($actions as $action): ?>
            <?php if (is_array($action)): ?>
                <?= component('ForgeUi:button', [
                    'variant' => $action['variant'] ?? 'primary',
                    'size' => $action['size'] ?? 'md',
                    'fullWidth' => $action['fullWidth'] ?? true,
                    'children' => $action['label'] ?? $action['text'] ?? '',
                    'type' => $action['type'] ?? 'button',
                    'class' => $action['class'] ?? null
                ], fromModule: true) ?>
            <?php else: ?>
                <?= $action ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
