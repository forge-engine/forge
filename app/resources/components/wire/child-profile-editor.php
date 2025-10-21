<div class="p">
    <?php if ($notice): ?>
    <div class="notice mb"><?= $notice ?></div>
    <?php endif; ?>
    <form wire:submit="save" novalidate>
        <label>Name <input type="text" wire:model.lazy="name" value="<?= $name ?>"></label>
        <label>Role <input type="text" wire:model.lazy="role" value="<?= $role ?>"></label>
        <button type="submit">Save</button>
    </form>
</div>