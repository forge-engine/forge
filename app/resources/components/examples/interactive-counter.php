<?php
/**
 * @var int $counter
 * @var int $step
 */
?>
<div <?= fw_id('interactive-counter') ?> class="card p-4 shadow-sm mb-4">
    <div class="card-header mb-3">
        <?= slot('header', '<h3 class="text-xl font-semibold">Interactive Counter</h3>') ?>
    </div>
    <div class="card-body">
        <?= slot('help_text') ?>
        
        <div class="flex gap-2 mb-4">
            <button fw:click="increment" class="btn btn-primary">+</button>
            <button fw:click="decrement" class="btn btn-primary">-</button>
            <button fw:click="reset" fw:param-value="0" class="btn btn-secondary">Reset</button>
        </div>
        
        <div fw:target class="text-2xl font-bold mb-4">
            Count: <?= $counter ?>
        </div>
        
        <div class="mt-4">
            <label class="block mb-2">Step:</label>
            <input type="number" fw:model="step" value="<?= $step ?>" class="form-control mb-2" />
            <button fw:click="incrementBy" fw:param-step="<?= $step ?>" class="btn btn-sm">
                Increment by <?= $step ?>
            </button>
        </div>
    </div>
    <div class="card-footer">
        <?= slot('footer') ?>
    </div>
</div>
