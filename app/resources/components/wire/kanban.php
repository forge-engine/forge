<?php /** @var array $columns; @var int|null $editingId; @var string $editText; @var string|null $composeCol; @var string $newText; */ ?>
<style>
.kanban {
    gap: 1rem;
    /* spacing between columns */
}

.kanban__col {
    display: flex;
    flex-direction: column;
    min-height: 400px;
    border-radius: 8px;
}

.kanban__col header {
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
}

.kanban__col:nth-child(1) {
    background: #fef8f8;
    /* soft red tint for To Do */
}

.kanban__col:nth-child(2) {
    background: #f8fdf8;
    /* soft green tint for Doing */
}

.kanban__col:nth-child(3) {
    background: #f8f9fe;
    /* soft blue tint for Done */
}

.kanban__card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    gap: 0.5rem;
    transition: box-shadow 0.15s ease;
}

.kanban__card:hover {
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
}

.kanban__card .task-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.kanban__card .task-title {
    font-size: 0.95rem;
    font-weight: 500;
    line-height: 1.3;
    flex: 1;
}
</style>
<div class="kanban grid grid--3 gap" wire:loading.class="opacity-50">
    <?php foreach ($columns as $ci => $col): ?>
    <div class="kanban__col card p-sm">
        <header class="flex justify-between items-center mb-xs">
            <h3 class="m-0"><?= e($col['title']) ?></h3>
            <button class="button button--sm" wire:click="compose('<?= $col['id'] ?>')">+ Add</button>
        </header>

        <ul class="list-reset flex flex-col gap-xs">
            <?php foreach ($col['cards'] as $ti => $card): ?>
            <li class="kanban__card p-xs border rounded flex items-center justify-between">
                <div class="flex items-center gap-xs">
                    <button class="button button--icon" wire:click="moveLeft(<?= (int)$card['id'] ?>)" <?= $ci===0 ? 'disabled' : '' ?>>⟵</button>
                    <button class="button button--icon" wire:click="moveRight(<?= (int)$card['id'] ?>)" <?= $ci===count($columns)-1 ? 'disabled' : '' ?>>⟶</button>
                </div>

                <div class="flex-1 px-xs">
                    <?php if ($editingId === (int)$card['id']): ?>
                    <input type="text" class="form--input" wire:model.debounce="editText" value="<?= e($editText) ?>" placeholder="Title…" />
                    <?php else: ?>
                    <span><?= e($card['title']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-2xs">
                    <button class="button button--icon" wire:click="moveUp(<?= (int)$card['id'] ?>)">↑</button>
                    <button class="button button--icon" wire:click="moveDown(<?= (int)$card['id'] ?>)">↓</button>

                    <?php if ($editingId === (int)$card['id']): ?>
                    <button class="button button--sm" wire:click="saveEdit(<?= (int)$card['id'] ?>)">Save</button>
                    <button class="button button--ghost button--sm" wire:click="cancelEdit">Cancel</button>
                    <?php else: ?>
                    <button class="button button--ghost button--sm" wire:click="edit(<?= (int)$card['id'] ?>)">Edit</button>
                    <button class="button button--danger button--sm" wire:click="remove(<?= (int)$card['id'] ?>)">✕</button>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <footer class="mt-sm">
            <?php if ($composeCol === $col['id']): ?>
            <div class="flex gap-xs">
                <input type="text" class="form--input flex-1" wire:model.debounce="newText" value="<?= e($newText) ?>" placeholder="New card title…" />
                <button class="button" wire:click="create('<?= $col['id'] ?>')">Add</button>
                <button class="button button--ghost" wire:click="cancelCompose">Cancel</button>
            </div>
            <?php endif; ?>
        </footer>
    </div>
    <?php endforeach; ?>
</div>