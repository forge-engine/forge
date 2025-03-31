<?php
 use App\Modules\ForgeStorage\Dto\StorageDto;

/*** @var StorageDto $record */

?>
<style>
.file-list-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: var(--space-unit);
}

.file-list-table th,
.file-list-table td {
    padding: var(--space-xs);
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

.file-list-table th {
    font-weight: bold;
}

.file-list-table tr:last-child td {
    border-bottom: none;
}

.file-actions a,
.file-actions button {
    margin-right: var(--space-xxs);
    cursor: pointer;
}

.more-actions {
    position: relative;
}

.more-actions-button {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-size: 1rem;
}

.more-actions-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    padding: var(--space-xxs);
    z-index: 10;
    min-width: 120px;
    display: none;
    /* Initially hidden */
}

.more-actions:hover .more-actions-dropdown,
.more-actions-dropdown:focus-within {
    display: block;
}

.more-actions-dropdown a,
.more-actions-dropdown button {
    display: block;
    padding: var(--space-xxs) var(--space-sm);
    text-decoration: none;
    color: var(--color-text);
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.more-actions-dropdown a:hover,
.more-actions-dropdown button:hover {
    background-color: var(--color-background);
}
</style>
<h3 class="section--title">Files in Bucket: <?= htmlspecialchars($currentBucket ?? 'N/A') ?></h3>
<table class="file-list-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Last Modified</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($storageRecords)): ?>
        <tr>
            <td colspan="4">No files found in this bucket.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($storageRecords as $record): ?>
        <tr>
            <td><?= basename($record->path) ?></td>
            <td><?= $record->formatSize() ?></td>
            <td><?= $record->created_at->format('Y:m:d') ?? 'N/A' ?></td>
            <td class="more-actions">
                <button class="more-actions-button">•••</button>
                <div class="more-actions-dropdown">
                    <a href="/file/<?= $record->bucket ?>/<?= $record->path ?>" target="_blank">View</a>
                    <a href="/temporary-url/<?= $record->bucket ?>/<?= $record->id ?>/<?= $record->path ?>/3600">Temporary URL</a>
                    <form hx-post="/admin/files/delete/<?= $record->bucket ?>/<?= urlencode($record->path) ?>" hx-target="#file-list-container" hx-swap="outerHTML">
                        <button type="submit" class="button--link button--error">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>