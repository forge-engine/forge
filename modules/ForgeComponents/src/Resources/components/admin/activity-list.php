<?php

$activities = $activities ?? [];
$maxItems = $maxItems ?? 10;

$containerClasses = class_merge(['space-y-4'], $class ?? '');
?>
<div class="<?= $containerClasses ?>">
    <?php if (empty($activities)): ?>
        <p class="text-sm text-gray-500 text-center py-4">No recent activity</p>
    <?php else: ?>
        <?php foreach (array_slice($activities, 0, $maxItems) as $activity): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <div class="flex items-center">
                    <?php if (isset($activity['icon'])): ?>
                        <div class="w-10 h-10 <?= $activity['iconBg'] ?? 'bg-blue-100' ?> rounded-full flex items-center justify-center mr-3">
                            <?= $activity['icon'] ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            <?= e($activity['title'] ?? '') ?>
                        </p>
                        <?php if (isset($activity['time'])): ?>
                            <p class="text-xs text-gray-500">
                                <?= e($activity['time']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (isset($activity['badge'])): ?>
                    <div>
                        <?= component('ForgeUi:badge', [
                            'variant' => $activity['badgeVariant'] ?? 'primary',
                            'size' => 'xs',
                            'children' => $activity['badge']
                        ], fromModule: true) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
