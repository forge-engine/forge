<?php

use App\Modules\ForgeUi\DesignTokens;

$variant = $variant ?? ($type ?? 'info');
$dismissible = $dismissible ?? false;

$baseClasses = DesignTokens::alert($variant);
$classes = class_merge($baseClasses, $class ?? '');
?>
<?php if ($variant): ?>
<div class="<?= $classes ?>" role="alert">
    <div class="flex items-start">
        <?php if (isset($slots['icon'])): ?>
            <div class="flex-shrink-0 mr-3">
                <?= $slots['icon'] ?>
            </div>
        <?php endif; ?>
        <div class="flex-1">
            <?php if (isset($slots['title'])): ?>
                <h4 class="font-semibold mb-1">
                    <?= $slots['title'] ?>
                </h4>
            <?php endif; ?>
            <div>
                <?= $slots['default'] ?? $children ?? '' ?>
            </div>
        </div>
        <?php if ($dismissible || isset($slots['actions'])): ?>
            <div class="flex-shrink-0 ml-3">
                <?php if (isset($slots['actions'])): ?>
                    <?= $slots['actions'] ?>
                <?php elseif ($dismissible): ?>
                    <button type="button" class="fw-alert-dismiss text-current opacity-70 hover:opacity-100 transition-opacity" aria-label="Dismiss">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
