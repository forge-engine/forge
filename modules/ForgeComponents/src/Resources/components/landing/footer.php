<?php

$theme = $theme ?? 'light';
$variant = $variant ?? 'default';
$links = $slots['links'] ?? $links ?? [];
$copyrightText = $slots['copyright'] ?? $copyright ?? null;

$themeClasses = $theme === 'dark'
    ? ['bg-gray-900', 'text-gray-300']
    : ['bg-gray-50', 'text-gray-700'];

$containerClasses = class_merge(['container', 'mx-auto', 'px-4', 'py-12'], $themeClasses, $class ?? '');
?>
<footer class="<?= $containerClasses ?>">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <?php if (isset($slots['column1']) || isset($column1)): ?>
            <div><?= $slots['column1'] ?? $column1 ?? '' ?></div>
        <?php endif; ?>

        <?php if (isset($slots['column2']) || isset($column2)): ?>
            <div><?= $slots['column2'] ?? $column2 ?? '' ?></div>
        <?php endif; ?>

        <?php if (isset($slots['column3']) || isset($column3)): ?>
            <div><?= $slots['column3'] ?? $column3 ?? '' ?></div>
        <?php endif; ?>

        <?php if (isset($slots['column4']) || isset($column4)): ?>
            <div><?= $slots['column4'] ?? $column4 ?? '' ?></div>
        <?php endif; ?>
    </div>

    <?php if (isset($slots['default'])): ?>
        <div class="mb-8">
            <?= $slots['default'] ?>
        </div>
    <?php endif; ?>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-8 flex flex-col md:flex-row justify-between items-center">
        <?php if ($copyrightText): ?>
            <div class="mb-4 md:mb-0">
                <?= $copyrightText ?>
            </div>
        <?php else: ?>
            <div class="mb-4 md:mb-0">
                &copy; <?= date('Y') ?> All rights reserved.
            </div>
        <?php endif; ?>

        <?php if (!empty($links) && is_array($links)): ?>
            <div class="flex flex-wrap gap-4">
                <?php foreach ($links as $link): ?>
                    <?php if (is_array($link)): ?>
                        <a href="<?= e($link['url'] ?? '#') ?>" class="hover:text-gray-900 dark:hover:text-white transition-colors">
                            <?= e($link['text'] ?? '') ?>
                        </a>
                    <?php else: ?>
                        <?= $link ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</footer>
