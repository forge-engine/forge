<?php

$collapsed = $collapsed ?? false;
$logo = $logo ?? null;
$brand = $brand ?? 'Admin';
$showCloseButton = $showCloseButton ?? true;
$items = $slots['items'] ?? $items ?? [];

$navClasses = class_merge(['flex', 'flex-col', 'h-full'], $class ?? '');
?>
<nav class="<?= $navClasses ?>">
  <div class="p-4 border-b border-gray-200 flex items-center justify-between">
    <div class="flex items-center">
      <?php if ($logo): ?>
        <img src="<?= e($logo) ?>" alt="Logo" class="h-8">
      <?php else: ?>
        <div class="text-xl font-bold text-gray-900"><?= e($brand) ?></div>
      <?php endif; ?>
    </div>

    <?php if ($showCloseButton): ?>
      <button type="button" data-sidebar-close
        class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
        aria-label="Close sidebar">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    <?php endif; ?>
  </div>

  <div class="flex-1 overflow-y-auto py-4">
    <?php if (!empty($items) && is_array($items)): ?>
      <?php foreach ($items as $item): ?>
        <?php if (is_array($item)): ?>
          <?php
          $isActive = $item['active'] ?? false;
          $baseClasses = ['flex', 'items-center', 'px-4', 'py-3', 'text-sm', 'font-medium', 'transition-colors'];
          $stateClasses = $isActive
            ? ['bg-blue-50', 'text-blue-600', 'border-r-2', 'border-blue-600']
            : ['text-gray-700', 'hover:bg-gray-50'];
          $itemClasses = class_merge($baseClasses, $stateClasses);
          ?>
          <a href="<?= e($item['url'] ?? '#') ?>" class="<?= $itemClasses ?>" data-sidebar-link>
            <?php if (isset($item['icon'])): ?>
              <span class="mr-3"><?= $item['icon'] ?></span>
            <?php endif; ?>
            <?php if (!$collapsed): ?>
              <span><?= e($item['text'] ?? '') ?></span>
            <?php endif; ?>
            <?php if (isset($item['badge'])): ?>
              <span class="ml-auto">
                <?= component('ForgeUi:badge', [
                  'variant' => 'primary',
                  'size' => 'xs',
                  'children' => $item['badge']
                ], fromModule: true) ?>
              </span>
            <?php endif; ?>
          </a>
        <?php else: ?>
          <?= $item ?>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php elseif (isset($slots['default'])): ?>
      <?= $slots['default'] ?>
    <?php endif; ?>
  </div>

  <?php if (isset($slots['footer'])): ?>
    <div class="p-4 border-t border-gray-200">
      <?= $slots['footer'] ?>
    </div>
  <?php endif; ?>
</nav>
