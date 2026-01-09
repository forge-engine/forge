<?php if ($data['type'] ?? ''): ?>
    <div class="text-accent<?= $data['type'] ?? '' ?> mb-sm">
        <?= $data['children'] ?? '' ?>
    </div>
<?php endif; ?>