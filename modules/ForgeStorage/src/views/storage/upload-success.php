<div class="card">
    <h2 class="card--title alert alert--success">Success!</h2>
    <div class="card--body">
        <p><?= $message ?></p>
        <?php if (isset($filename)): ?>
        <p>Uploaded file: <span class="font-bold"><?= $filename ?></span></p>
        <?php endif; ?>
    </div>
</div>