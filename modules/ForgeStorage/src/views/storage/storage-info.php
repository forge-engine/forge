<div class="card">
    <h2 class="card--title">Storage Information</h2>
    <div class="card--body">
        <p>ID: <span class="font-bold"><?= $storage->id ?></span></p>
        <p>Path: <span class="font-bold"><?= $storage->path ?></span></p>
        <p>Size: <span class="font-bold"><?= $storage->size ?></span> bytes</p>
        <?php if (isset($storage->mime_type)): ?>
        <p>MIME Type: <span class="font-bold"><?= $storage->mime_type ?></span></p>
        <?php endif; ?>
        <?php if (isset($storage->expires_at)): ?>
        <p>Expires At: <span class="font-bold"><?= $storage->expires_at ?></span></p>
        <?php endif; ?>
    </div>
</div>