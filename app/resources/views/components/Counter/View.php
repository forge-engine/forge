<div class="counter">
    <button wire:click="decrement">–</button>
    <span><?= $count ?> (<?= $parity ?>)</span>
    <button wire:click="increment">+</button>
    <input id="counter-search" type="text" wire:model.debounce.300ms="query" placeholder="Type…" />
</div>