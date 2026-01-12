<?php
/**
 * @var string $formName
 * @var string $formEmail
 * @var string $formMessage
 */
?>
<div <?= fw_id('form-submission-demo') ?> class="card p-4 shadow-sm mb-4">
    <div class="card-header mb-3">
        <?= slot('header', '<h3 class="text-xl font-semibold">Form Submission Demo</h3>') ?>
    </div>
    <div class="card-body">
        <?= slot('help_text') ?>
        
        <form fw:submit="saveForm" class="space-y-4">
            <div>
                <label class="block mb-2">Name (required, min 3 chars):</label>
                <input type="text" fw:model="formName" value="<?= e($formName) ?>" class="form-control" />
                <p class="text-red-600 text-sm" fw:validation-error="formName"></p>
            </div>
            
            <div>
                <label class="block mb-2">Email (required, valid email):</label>
                <input type="email" fw:model="formEmail" value="<?= e($formEmail) ?>" class="form-control" />
                <p class="text-red-600 text-sm" fw:validation-error="formEmail"></p>
            </div>
            
            <div fw:target>
                <?php if ($formMessage): ?>
                    <div class="alert alert-success mb-3">
                        <?= e($formMessage) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="flex gap-2">
                <?= slot('submit_button', '<button type="submit" class="btn btn-primary">Save Form</button>') ?>
            </div>
            
            <div fw:loading class="text-info mt-2">
                Saving...
            </div>
        </form>
    </div>
    <div class="card-footer">
        <?= slot('footer') ?>
    </div>
</div>
