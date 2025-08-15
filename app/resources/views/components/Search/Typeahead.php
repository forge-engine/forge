<?php
/** @var string $query */
/** @var array $results */
/** @var bool $open */
/** @var string|null $notice */
?>
<div class="typeahead">
    <div class="flex gap items-center">
        <input type="text" placeholder="Search products…" autocomplete="off" wire:model.debounce value="<?= e($query) ?>" />
        <?php if ($notice): ?>
        <small class="badge"><?= e($notice) ?></small>
        <?php endif; ?>
        <span class="spinner" <?= $open ? '' : 'hidden' ?> wire:loading>…</span>
    </div>

    <?php if ($open && !empty($results)): ?>
    <ul class="menu shadow mt-2">
        <?php foreach ($results as $r): ?>
        <li>
            <button wire:click="choose(<?= (int)$r['id'] ?>)">
                <?= e($r['name']) ?> — $<?= number_format((float)$r['price'], 2) ?>
            </button>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php elseif ($open): ?>
    <div class="empty mt-2">No results</div>
    <?php endif; ?>
</div>