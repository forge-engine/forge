<?php
/**
 * @var string $immediateValue
 * @var string $lazyValue
 * @var string $deferValue
 * @var string $debounceValue
 * @var string $customDebounceValue
 */
?>
<div <?= fw_id('model-binding-demo') ?> class="card p-4 shadow-sm mb-4">
    <div class="card-header mb-3">
        <?= slot('header', '<h3 class="text-xl font-semibold">Model Binding Demo</h3>') ?>
    </div>
    <div class="card-body space-y-4">
        <?= slot('help_text') ?>
        
        <div class="border-b pb-4">
            <label class="block mb-2 font-semibold">Immediate (fw:model) - Updates on every keystroke:</label>
            <input type="text" fw:model="immediateValue" value="<?= e($immediateValue) ?>" class="form-control" />
            <div fw:target class="mt-2 text-sm text-gray-600">
                <strong>Value:</strong> <?= e($immediateValue) ?>
            </div>
        </div>
        
        <div class="border-b pb-4">
            <label class="block mb-2 font-semibold">Lazy (fw:model.lazy) - Updates on blur/change:</label>
            <input type="text" fw:model.lazy="lazyValue" value="<?= e($lazyValue) ?>" class="form-control" />
            <div fw:target class="mt-2 text-sm text-gray-600">
                <strong>Value:</strong> <?= e($lazyValue) ?>
            </div>
        </div>
        
        <div class="border-b pb-4">
            <label class="block mb-2 font-semibold">Defer (fw:model.defer) - Updates only when action triggered:</label>
            <input type="text" fw:model.defer="deferValue" value="<?= e($deferValue) ?>" class="form-control" />
            <button fw:click="saveForm" class="btn btn-sm mt-2">Update Defer Value</button>
            <div fw:target class="mt-2 text-sm text-gray-600">
                <strong>Value:</strong> <?= e($deferValue) ?>
            </div>
        </div>
        
        <div class="border-b pb-4">
            <label class="block mb-2 font-semibold">Debounce (fw:model.debounce) - 600ms default:</label>
            <input type="text" fw:model.debounce="debounceValue" value="<?= e($debounceValue) ?>" class="form-control" />
            <div fw:target class="mt-2 text-sm text-gray-600">
                <strong>Value:</strong> <?= e($debounceValue) ?>
            </div>
        </div>
        
        <div>
            <label class="block mb-2 font-semibold">Custom Debounce (fw:model.debounce.300ms) - 300ms:</label>
            <input type="text" fw:model.debounce.300ms="customDebounceValue" value="<?= e($customDebounceValue) ?>" class="form-control" />
            <div fw:target class="mt-2 text-sm text-gray-600">
                <strong>Value:</strong> <?= e($customDebounceValue) ?>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <?= slot('footer') ?>
    </div>
</div>
