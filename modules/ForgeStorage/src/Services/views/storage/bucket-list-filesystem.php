<div class="card">
    <h2 class="card--title">File System Buckets</h2>
    <div class="card--body">
        <?php if (empty($buckets)): ?>
        <p>No buckets found in the file system.</p>
        <?php else: ?>
        <ul>
            <?php foreach ($buckets as $bucket): ?>
            <li><?= $bucket ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>