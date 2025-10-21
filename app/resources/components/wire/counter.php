<div class="counter">
    <button wire:click="decrement">–</button>
    <span><?= $count ?> (<?= $parity ?>)</span>
    <button wire:click="increment">+</button>
</div>