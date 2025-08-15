<?php
/** @var array $items */
/** @var int $total */
/** @var int $pages */
/** @var int $page */
/** @var int $perPage */
/** @var string $sort */
/** @var string $dir */
/** @var string $query */
/** @var int|null $editingId */
/** @var string $draftName */
/** @var string $draftPrice */
/** @var array $errors */
/** @var string|null $notice */
?>

<div class="fw-products space-y">
    <div class="flex gap">
        <input wire:model.debounce.500ms="query" placeholder="Search products..." />
        <?php if ($notice): ?>
        <span class="badge"><?= e($notice) ?></span>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>
                    <button wire:click="setSort('id')">ID
                        <?= $sort==='id' ? ' ' . ($dir==='asc' ? '↑' : '↓') : '' ?>
                    </button>
                </th>
                <th><button wire:click="setSort('name')">Name<?= $sort==='name' ? ' ' . ($dir==='asc' ? '↑' : '↓') : '' ?></button></th>
                <th><button wire:click="setSort('price')">Price<?= $sort==='price' ? ' ' . ($dir==='asc' ? '↑' : '↓') : '' ?></button></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $p): ?>
            <tr>
                <td><?= (int)$p['id'] ?></td>
                <td><?= e($p['name']) ?></td>
                <td>$<?= number_format((float)$p['price'], 2) ?></td>
                <td class="text-right">
                    <button wire:click="edit(<?= (int)$p['id'] ?>)">Edit</button>
                    <button wire:click="remove(<?= (int)$p['id'] ?>)">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if ($editingId !== null): ?>
            <tr>
                <td colspan="4">
                    <form wire:submit="save" class="card p">
                        <div class="grid">
                            <label>
                                Name
                                <input type="text" wire:model.lazy="draftName" value="<?= e($draftName) ?>" />
                                <?php if (!empty($errors['name'])): ?>
                                <small class="error"><?= e($errors['name']) ?></small>
                                <?php endif; ?>
                            </label>
                            <label>
                                Price
                                <input type="number" step="0.01" wire:model.lazy="draftPrice" value="<?= e($draftPrice) ?>" />
                                <?php if (!empty($errors['price'])): ?>
                                <small class="error"><?= e($errors['price']) ?></small>
                                <?php endif; ?>
                            </label>
                        </div>
                        <div class="flex gap">
                            <button type="submit">Save</button>
                            <button type="button" wire:click="cancel">Cancel</button>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="flex gap items-center">
        <span><?= $total ?> items</span>
        <div class="ml-auto flex gap">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <button wire:click="goTo(<?= $i ?>)" <?= $i===$page ? 'disabled' : '' ?>>
                <?= $i ?>
            </button>
            <?php endfor; ?>
        </div>
    </div>
</div>