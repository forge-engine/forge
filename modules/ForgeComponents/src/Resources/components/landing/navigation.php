<?php

$theme = $theme ?? 'light';
$sticky = $sticky ?? false;
$menuItems = $slots['menu'] ?? $menuItems ?? [];
$logoContent = $slots['logo'] ?? $logo ?? null;
$brandText = $slots['brand'] ?? $brand ?? null;

$themeClasses = $theme === 'dark'
  ? ['bg-gray-900', 'text-white']
  : ['bg-white', 'text-gray-900', 'border-b', 'border-gray-200'];

$stickyClasses = $sticky ? ['sticky', 'top-0', 'z-50'] : [];
$baseClasses = array_merge(['w-full'], $stickyClasses, $themeClasses);
$navClasses = class_merge($baseClasses, $class ?? '');
?>
<nav class="<?= $navClasses ?>">
  <div class="container mx-auto px-4">
    <div class="flex items-center justify-between h-16">
      <div class="flex items-center">
        <?php if ($logoContent): ?>
          <div class="flex-shrink-0">
            <?= is_string($logoContent) && !str_starts_with($logoContent, '<')
              ? '<img src="' . e($logoContent) . '" alt="Logo" class="h-8">'
              : $logoContent ?>
          </div>
        <?php endif; ?>

        <?php if ($brandText): ?>
          <div class="ml-4 text-xl font-bold">
            <?= e($brandText) ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($menuItems)): ?>
        <div class="hidden md:flex items-center space-x-4">
          <?php foreach ($menuItems as $item): ?>
            <?php if (is_array($item)): ?>
              <?php
              $isActive = $item['active'] ?? false;
              $baseClasses = ['px-3', 'py-2', 'rounded-md', 'text-sm', 'font-medium', 'hover:bg-gray-100', 'dark:hover:bg-gray-800', 'transition-colors'];
              $activeClasses = $isActive ? ['bg-gray-100', 'dark:bg-gray-800'] : [];
              $itemClasses = class_merge($baseClasses, $activeClasses);
              ?>
              <a href="<?= e($item['url'] ?? '#') ?>" class="<?= $itemClasses ?>">
                <?= e($item['text'] ?? '') ?>
              </a>
            <?php else: ?>
              <?= $item ?>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="flex items-center space-x-4">
        <?php if (isset($slots['actions'])): ?>
          <?= $slots['actions'] ?>
        <?php elseif (isset($user)): ?>
          <?= component('ForgeUi:dropdown', [
            'id' => 'user-menu',
            'slots' => [
              'trigger' => component('ForgeUi:avatar', [
                'src' => $user['avatar'] ?? null,
                'initials' => $user['initials'] ?? substr($user['name'] ?? 'U', 0, 2),
                'size' => 'sm'
              ], fromModule: true),
              'menu' => $userMenu ?? ''
            ]
          ], fromModule: true) ?>
        <?php else: ?>
          <?php if (isset($loginUrl)): ?>
            <?= component('ForgeUi:button', [
              'variant' => 'ghost',
              'size' => 'sm',
              'children' => 'Login'
            ], fromModule: true) ?>
          <?php endif; ?>
          <?php if (isset($signupUrl)): ?>
            <?= component('ForgeUi:button', [
              'variant' => 'primary',
              'size' => 'sm',
              'children' => 'Sign Up'
            ], fromModule: true) ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
