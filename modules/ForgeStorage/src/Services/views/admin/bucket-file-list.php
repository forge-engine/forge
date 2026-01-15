<?php

use App\Modules\ForgeStorage\Dto\StorageDto;
use App\Modules\ForgeStorage\Dto\BucketDto;
use Forge\Core\Helpers\File;
use Forge\Core\Helpers\Url;
use Forge\Core\View\View;

/***
@var BucketDto $bucket
@var StorageDto[] $storageRecords
*/

View::layout(name: "main", loadFromModule: false);
?>
<style>
.file-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-lg);
    padding: var(--space-md);
    background: var(--color-surface);
    border-radius: var(--border-radius);
}

.file-header-info {
    flex: 1;
}

.file-header-actions {
    flex-shrink: 0;
}

.bucket-stats {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
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
    background-color: var(--color-border) !important;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    padding: var(--space-xxs);
    z-index: 40;
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
</style>

<div class="container">
    <section class="section">
        <div class="file-header">
            <div class="file-header-info">
                <h1 class="section--title">Bucket: <?= $bucket->name ?></h1>
                <div class="bucket-stats">
                    <div>URL: <a href="<?=Url::getUrl()?>"><?=Url::getUrl()?></a> </div>
                    <div>Total Space Used: <?= File::bucketSize($bucket->name) ?> / <?= File::countDirectoryFiles($bucket->name)?> Items</div>
                </div>
            </div>

            <div class="file-header-actions">
                <form hx-post="/upload/<?=$bucket->name?>" hx-target="#upload-status" class="inline-block" enctype="multipart/form-data">
                    <label class="button button--primary">
                        Upload Files
                        <input type="file" name="file" class="hidden" multiple onchange="this.form.requestSubmit()">
                    </label>
                </form>
            </div>
        </div>

        <table class="file-list-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Last Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($storageRecords)): ?>
                <tr>
                    <td colspan="4">No files found in this bucket</td>
                </tr>
                <?php else: ?>
                <?php foreach ($storageRecords as $record): ?>
                <tr>
                    <td><?= basename($record->path) ?></td>
                    <td><?= $record->formatSize() ?></td>
                    <td><?= $record->created_at->format('M d, Y H:i') ?></td>

                    <td class="more-actions">
                        <button class="more-actions-button">•••</button>
                        <div class="more-actions-dropdown">
                            <a href="/file/<?= $record->bucket ?>/<?= $record->path ?>" target="_blank" class="button--link">
                                View
                            </a>
                            <a href="/temporary-url/<?= $record->bucket ?>/<?= $record->id ?>/<?= $record->path ?>/3600">Temporary URL</a>
                            <form hx-delete="/admin/files/delete/<?= $record->bucket ?>/<?= urlencode($record->path) ?>" hx-confirm="Are you sure?">
                                <button type="submit" class="button--link button--error">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div id="upload-status" class="mt-md"></div>
    </section>
</div>