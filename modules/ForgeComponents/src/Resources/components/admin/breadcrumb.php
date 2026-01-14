<?php

$items = $slots['items'] ?? $items ?? [];
$separator = $separator ?? '/';

$navClasses = class_merge(['flex', 'items-center', 'space-x-2', 'text-sm'], $class ?? '');
?>
<nav class="<?= $navClasses ?>" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <?php
        if (is_array($items)) {
            $lastIndex = count($items) - 1;
            foreach ($items as $index => $item) {
                if (is_array($item)) {
                    $isLast = $index === $lastIndex;
                    if ($isLast) {
                        echo '<li class="text-gray-900 font-medium">' . e($item['text'] ?? '') . '</li>';
                    } else {
                        echo '<li><a href="' . e($item['url'] ?? '#') . '" class="text-gray-500 hover:text-gray-700 transition-colors">' . e($item['text'] ?? '') . '</a></li>';
                        echo '<li class="text-gray-400">' . e($separator) . '</li>';
                    }
                } else {
                    echo '<li>' . $item . '</li>';
                }
            }
        } elseif (isset($slots['default'])) {
            echo $slots['default'];
        }
        ?>
    </ol>
</nav>
