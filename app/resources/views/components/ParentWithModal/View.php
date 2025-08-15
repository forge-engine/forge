<div>
    <button type="button" wire:click="open">Edit profile</button>

    <?php if ($show): ?>
    <div class="modal-backdrop">
        <div class="modal">
            <button class="close" wire:click="close">×</button>
            <?= wire_name("child-profile-editor", ['userId'=>$userId], 'profile-modal') ?>
        </div>
    </div>
    <?php endif; ?>
</div>