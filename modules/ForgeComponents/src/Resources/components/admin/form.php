<?php

$method = $method ?? 'POST';
$action = $action ?? '';
$errors = $errors ?? [];

$formClasses = class_merge(['space-y-6'], $class ?? '');
?>
<form method="<?= e($method) ?>" action="<?= e($action) ?>" class="<?= $formClasses ?>">
    <?= csrf_input() ?>

    <?php if (!empty($errors)): ?>
        <div class="mb-4">
            <?= component('ForgeUi:alert', [
                'variant' => 'error',
                'children' => 'Please correct the errors below.'
            ], fromModule: true) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($slots['default'])): ?>
        <?= $slots['default'] ?>
    <?php endif; ?>

    <?php if (isset($slots['actions'])): ?>
        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
            <?= $slots['actions'] ?>
        </div>
    <?php else: ?>
        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
            <?php if (isset($cancelUrl)): ?>
                <a href="<?= e($cancelUrl) ?>" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                    Cancel
                </a>
            <?php endif; ?>
            <?= component('ForgeUi:button', [
                'variant' => 'primary',
                'type' => 'submit',
                'children' => $submitText ?? 'Save'
            ], fromModule: true) ?>
        </div>
    <?php endif; ?>
</form>
