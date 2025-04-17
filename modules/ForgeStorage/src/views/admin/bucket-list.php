<?php
use App\Modules\ForgeStorage\Dto\BucketDto;
use Forge\Core\Helpers\File;
use Forge\Core\View\View;

/*** @var BucketDto[] $buckets */

View::layout(name: "main", loadFromModule: false);
?>
<style>
.bucket-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: var(--space-unit);
}

.bucket-table th,
.bucket-table td {
    padding: var(--space-sm);
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

.bucket-actions {
    text-align: right;
}

.create-bucket-card {
    margin-bottom: var(--space-lg);
    padding: var(--space-md);
    background: var(--color-surface);
    border-radius: var(--border-radius);
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
</style>

<div class="container">
    <section class="section">
        <h1 class="section--title">Buckets</h1>

        <div class="create-bucket-card">

            <form hx-post="/admin/buckets/create" hx-target="#bucket-creation-status">
                <div class="form--group">
                    Bucket Name:
                    <input type="text" name="name" class="form--input" placeholder="Enter bucket name" required>
                    <button type="submit" class="button button--primary">
                        Create Bucket
                    </button>
                </div>
                <div id="bucket-creation-status" class="mt-sm"></div>
            </form>
        </div>

        <table class="bucket-table">
            <thead>
                <tr>
                    <th>Bucket Name</th>
                    <th>Region</th>
                    <th>Total Files</th>
                    <th>Total Size</th>
                    <th class="bucket-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($buckets)): ?>
                <tr>
                    <td colspan="5">No buckets found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($buckets as $bucket): ?>
                <tr>
                    <td>
                        <a href="/admin/bucket-files/<?= $bucket->name ?>" class="link">
                            <?= $bucket->name ?>
                        </a>
                    </td>
                    <td>Local</td>
                    <td><?= File::countDirectoryFiles($bucket->name)?></td>
                    <td><?= File::bucketSize($bucket->name) ?></td>
                    <td class="more-actions">
                        <button class="more-actions-button">•••</button>
                        <div class="more-actions-dropdown">

                            <form hx-post="/admin/buckets/delete/<?= $bucket->name ?>" hx-target="#file-list-container" hx-swap="outerHTML">
                                <button type="submit" class="button--link button--error">Delete</button>
                            </form>
                        </div>
                    </td>

                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>