<?php
use Forge\Core\View\View;

View::layout(name: "main", loadFromModule: false);
?>
<style>
/* Add any missing CSS here to match the image */
.file-manager-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: var(--space-md);
    border-bottom: 1px solid var(--color-border);
    margin-bottom: var(--space-lg);
}

.file-manager-header-left {
    display: flex;
    align-items: center;
}

.file-manager-header-left h1 {
    margin-right: var(--space-lg);
}

.file-actions button {
    margin-left: var(--space-sm);
}

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

.bucket-list-container {
    margin-bottom: var(--space-lg);
    padding: var(--space-md);
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
}

.bucket-list-container h2 {
    margin-top: 0;
    margin-bottom: var(--space-sm);
}

.bucket-item {
    padding: var(--space-xs) var(--space-sm);
    margin-bottom: var(--space-xxs);
    cursor: pointer;
    transition: background-color 0.2s ease;
    border-radius: var(--border-radius-sm);
}

.bucket-item:hover {
    background-color: var(--color-background);
}

.current-bucket-info {
    padding: var(--space-md);
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    margin-bottom: var(--space-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.current-bucket-name-display {
    font-size: 1.2rem;
    font-weight: bold;
}
</style>

<div class="container">
    <section class="section">
        <h1 class="section--title">File Manager</h1>
    </section>

    <section class="section current-bucket-info">
        <h2 class="section--title current-bucket-name-display" id="current-bucket-name">Select a Bucket</h2>
        <div>
            <input type="text" class="form--input" placeholder="Start typing to filter..." hx-get="/admin/files/search" hx-trigger="keyup delay:500" hx-target="#file-list-container">
        </div>
        <div class="file-actions">
            <form action="/upload" method="post" enctype="multipart/form-data" hx-post="/upload" hx-target="#upload-status-main" class="inline-block">
                <label for="file-upload" class="button button--primary">Upload Files</label>
                <input id="file-upload" type="file" name="file" class="hidden" multiple onchange="this.form.requestSubmit()">
            </form>
            <div id="upload-status-main" class="mt-sm">
            </div>
        </div>
    </section>

    <section class="section bucket-list-container">
        <h2 class="section--title">Buckets</h2>
        <div id="bucket-list-container" hx-get="/admin/buckets/list" hx-trigger="load" hx-target="#bucket-list-container">
            <p>Loading buckets...</p>
        </div>
    </section>

    <section class="section file-list-table-wrapper">
        <div id="file-list-container">
            <p>Select a bucket to view its files.</p>
        </div>
    </section>

    <section class="section">
        <h2 class="section--title">Create New Bucket</h2>
        <div class="card">
            <div class="card--body">
                <form hx-post="/admin/buckets/create" hx-target="#bucket-creation-status">
                    <div class="form--group">
                        <label for="bucketName" class="form--label">Bucket Name:</label>
                        <input type="text" name="name" id="bucketName" class="form--input" required>
                    </div>
                    <button type="submit" class="button button--secondary">Create Bucket</button>
                </form>
                <div id="bucket-creation-status" class="mt-sm">
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <h2 class="section--title">Delete Bucket</h2>
        <div class="card">
            <div class="card--body">
                <form hx-post="/admin/buckets/delete" hx-target="#bucket-deletion-status">
                    <div class="form--group">
                        <label for="deleteBucketName" class="form--label">Bucket Name to Delete:</label>
                        <input type="text" name="name" id="deleteBucketName" class="form--input" required>
                    </div>
                    <button type="submit" class="button button--error">Delete Bucket</button>
                </form>
                <div id="bucket-deletion-status" class="mt-sm">
                </div>
            </div>
        </div>
    </section>
</div>