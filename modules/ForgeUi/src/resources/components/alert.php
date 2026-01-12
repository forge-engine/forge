<?php
/**
 * @name ForgeUi:alert
 * @var string $type
 * @var string $children
 * @file ForgeUi/src/Resources/components/alert.php
 */
?>
<?php if ($type ?? ''): ?>
        <div class="text-accent<?= $type ?? '' ?> mb-sm">
            <?= $children ?? '' ?>
        </div>
<?php endif; ?>