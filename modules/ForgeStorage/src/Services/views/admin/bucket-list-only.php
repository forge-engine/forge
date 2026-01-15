<style>
.bucket-item {
    padding: var(--space-xs) var(--space-sm);
    margin-bottom: var(--space-xxs);
    cursor: pointer;
    transition: background-color 0.2s ease;
    border-radius: var(--border-radius-sm);
    display: inline-block;
    /* Display buckets in a row */
    margin-right: var(--space-sm);
}

.bucket-item:hover {
    background-color: var(--color-background);
}
</style>
<div>
    <?php if (empty($buckets)): ?>
    <p>No buckets found.</p>
    <?php else: ?>
    <?php foreach ($buckets as $bucket): ?>
    <span class="bucket-item" hx-get="/admin/files/list/<?= htmlspecialchars($bucket->name) ?>" hx-target="#file-list-container" hx-trigger="click" hx-swap="innerHTML" hx-on::after-request="document.getElementById('current-bucket-name').innerText = '<?= htmlspecialchars($bucket->name) ?>'">
        <?= htmlspecialchars($bucket->name) ?>
    </span>
    <?php endforeach; ?>
    <?php endif; ?>
</div>