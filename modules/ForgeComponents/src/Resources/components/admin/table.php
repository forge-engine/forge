<?php

$variant = $variant ?? 'default';
$striped = $striped ?? true;
$hoverable = $hoverable ?? true;
$bordered = $bordered ?? false;
$compact = $compact ?? false;
$actions = $actions ?? [];

$tableClasses = class_merge([], $class ?? '');
?>
<?= component('ForgeUi:table', [
    'variant' => $variant,
    'striped' => $striped,
    'hoverable' => $hoverable,
    'bordered' => $bordered,
    'compact' => $compact,
    'class' => $tableClasses,
    'slots' => [
        'header' => $slots['header'] ?? null,
        'body' => $slots['body'] ?? null,
        'footer' => $slots['footer'] ?? null,
        'empty' => $slots['empty'] ?? 'No data available'
    ]
], fromModule: true) ?>

<?php if (!empty($actions) && isset($slots['body'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('[data-action]');
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            const id = this.getAttribute('data-id');
            if (action && id) {
                window.dispatchEvent(new CustomEvent('admin:action', {
                    detail: { action, id }
                }));
            }
        });
    });
});
</script>
<?php endif; ?>
