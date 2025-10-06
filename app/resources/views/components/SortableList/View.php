<ul class="list">
    <?php foreach ($items as $idx => $item): ?>
    <li class="row">
        <span class="title"><?= $item['title'] ?></span>
        <span class="actions">
            <button type="button" wire:click="moveUp(<?= (int)$item['id'] ?>)" <?= $idx===0 ? 'disabled' : '' ?>>↑</button>
            <button type="button" wire:click="moveDown(<?= (int)$item['id'] ?>)" <?= $idx===count($items)-1 ? 'disabled' : '' ?>>↓</button>
        </span>
    </li>
    <?php endforeach; ?>
</ul>