<?php /** @var array $countries,$states,$cities; @var string $country,$state,$city */ ?>
<div class="grid gap-sm">
    <label>Country
        <select wire:model.lazy="country">
            <option value="">— Select —</option>
            <?php foreach ($countries as $o): ?>
            <option value="<?= $o['value'] ?>" <?= $country===$o['value'] ? 'selected' : '' ?>><?= $o['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>State
        <select wire:model.lazy="state" <?= $country ? '' : 'disabled' ?>>
            <option value="">— Select —</option>
            <?php foreach ($states as $o): ?>
            <option value="<?= $o['value'] ?>" <?= $state===$o['value'] ? 'selected' : '' ?>><?= $o['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>City
        <select wire:model.lazy="city" <?= ($country && $state) ? '' : 'disabled' ?>>
            <option value="">— Select —</option>
            <?php foreach ($cities as $o): ?>
            <option value="<?= $o['value'] ?>" <?= $city===$o['value'] ? 'selected' : '' ?>><?= $o['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <?php if ($country && $state && $city): ?>
    <p class="mt-xs text-sm">Selected: <?= "$country / $state / $city" ?></p>
    <?php endif; ?>
</div>