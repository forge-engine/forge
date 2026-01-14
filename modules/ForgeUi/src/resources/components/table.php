<?php

use App\Modules\ForgeUi\DesignTokens;

$variant = $variant ?? 'default';
$striped = $striped ?? false;
$hoverable = $hoverable ?? false;
$bordered = $bordered ?? false;
$compact = $compact ?? false;

$baseClasses = DesignTokens::table($bordered ? 'bordered' : $variant);
if ($striped) {
    $baseClasses[] = 'fw-table-striped';
}
if ($hoverable) {
    $baseClasses[] = 'fw-table-hoverable';
}
if ($compact) {
    $baseClasses[] = 'fw-table-compact';
}

$classes = class_merge($baseClasses, $class ?? '');
?>
<div class="overflow-x-auto">
    <table class="<?= $classes ?>">
        <?php if (isset($slots['header'])): ?>
            <thead class="bg-gray-50">
                <?= $slots['header'] ?>
            </thead>
        <?php endif; ?>
        <?php if (isset($slots['body'])): ?>
            <tbody class="bg-white divide-y divide-gray-200">
                <?= $slots['body'] ?>
            </tbody>
        <?php elseif (isset($slots['empty'])): ?>
            <tbody>
                <tr>
                    <td colspan="100%" class="px-6 py-4 text-center text-gray-500">
                        <?= $slots['empty'] ?>
                    </td>
                </tr>
            </tbody>
        <?php endif; ?>
        <?php if (isset($slots['footer'])): ?>
            <tfoot class="bg-gray-50">
                <?= $slots['footer'] ?>
            </tfoot>
        <?php endif; ?>
    </table>
</div>
