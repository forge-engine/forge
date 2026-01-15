<div class="card">
    <h2 class="card--title">Database Buckets</h2>
    <div class="card--body">
        <?php if (empty($buckets)): ?>
        <p>No buckets found in the database.</p>
        <?php else: ?>
        <ul>
            <?php foreach ($buckets as $bucket): ?>
            <li><?= $bucket->name ?> (ID: <?= $bucket->id ?>)</li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>