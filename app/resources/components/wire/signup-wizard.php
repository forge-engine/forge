<?php
/** @var int $step */
/** @var \App\DTO\SignupDTO $form */
/** @var array $errors */
/** @var string|null $notice */
?>
<style>
.wizard {
    max-width: 100%;
    margin: auto;
    font-family: system-ui, sans-serif;
    color: #333;
}

.steps {
    display: flex;
    justify-content: space-between;
    list-style: none;
    padding: 0;
    margin: 0 0 1rem;
    counter-reset: step;
}

.steps li {
    flex: 1;
    text-align: center;
    position: relative;
}

.steps li button {
    background: none;
    border: none;
    color: #666;
    font-size: 0.9rem;
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.2s;
}

.steps li.active button {
    font-weight: 600;
    color: #2563eb;
}

.steps li::before {
    counter-increment: step;
    content: counter(step);
    display: block;
    margin: auto;
    background: #ddd;
    color: #fff;
    width: 28px;
    height: 28px;
    line-height: 28px;
    border-radius: 50%;
    margin-bottom: 0.4rem;
    font-size: 0.85rem;
}

.steps li.active::before {
    background: #2563eb;
}

.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    padding: 1rem;
    margin-bottom: 1rem;
}

label {
    display: block;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

input[type="text"],
input[type="email"],
input[type="password"],
select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 0.9rem;
    margin-top: 0.3rem;
    box-sizing: border-box;
}

input:focus,
select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.error {
    display: block;
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.3rem;
}

.notice {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    border-radius: 6px;
    padding: 0.75rem;
    font-size: 0.85rem;
}

.flex {
    display: flex;
    align-items: center;
}

.gap {
    gap: 0.5rem;
}

.mt {
    margin-top: 1rem;
}

.mb {
    margin-bottom: 1rem;
}

button {
    background: #2563eb;
    border: none;
    color: white;
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

button:hover:not(:disabled) {
    background: #1d4ed8;
}
</style>
<div class="wizard" wire:loading.class="opacity-60">
    <ol class="steps mb">
        <li class="<?= $step>=1 ? 'active' : '' ?>"><button wire:click="goto(1)">Account</button></li>
        <li class="<?= $step>=2 ? 'active' : '' ?>"><button wire:click="goto(2)">Profile</button></li>
        <li class="<?= $step>=3 ? 'active' : '' ?>"><button wire:click="goto(3)">Preferences</button></li>
    </ol>

    <?php if ($notice): ?>
    <div class="notice mb"><?= e($notice) ?></div>
    <?php endif; ?>

    <form wire:submit="<?= $step < 3 ? 'next' : 'submit' ?>" novalidate>
        <?php if ($step === 1): ?>
        <div class="card p">
            <label>Email
                <input type="email" wire:model.lazy="form.email" value="<?= e($form->email) ?>" />
                <?php if (!empty($errors['email'])): ?>
                <small class="error"><?= e($errors['email']) ?></small>
                <?php endif; ?>
            </label>
            <div class="grid">
                <label>Password
                    <input type="password" wire:model.lazy="form.password" value="<?= e($form->password) ?>" />
                    <?php if (!empty($errors['password'])): ?>
                    <small class="error"><?= e($errors['password']) ?></small>
                    <?php endif; ?>
                </label>
                <label>Confirm
                    <input type="password" wire:model.lazy="form.confirmPassword" value="<?= e($form->confirmPassword) ?>" />
                    <?php if (!empty($errors['confirmPassword'])): ?>
                    <small class="error"><?= e($errors['confirmPassword']) ?></small>
                    <?php endif; ?>
                </label>
            </div>
        </div>
        <?php elseif ($step === 2): ?>
        <div class="card p">
            <label>Full name
                <input type="text" wire:model.debounce="form.fullName" value="<?= e($form->fullName) ?>" />
                <?php if (!empty($errors['fullName'])): ?>
                <small class="error"><?= e($errors['fullName']) ?></small>
                <?php endif; ?>
            </label>
            <div class="grid">
                <label>Company
                    <input type="text" wire:model.lazy="form.company" value="<?= e($form->company) ?>" />
                </label>
                <label>Role
                    <input type="text" wire:model.lazy="form.role" value="<?= e($form->role) ?>" />
                    <?php if (!empty($errors['role'])): ?>
                    <small class="error"><?= e($errors['role']) ?></small>
                    <?php endif; ?>
                </label>
            </div>
        </div>
        <?php else: ?>
        <div class="card p">
            <label>Plan
                <select wire:model.lazy="form.plan">
                    <option value="basic" <?= $form->plan==='basic' ? 'selected' : '' ?>>Basic</option>
                    <option value="pro" <?= $form->plan==='pro' ? 'selected' : '' ?>>Pro</option>
                    <option value="teams" <?= $form->plan==='teams' ? 'selected' : '' ?>>Teams</option>
                </select>
            </label>
            <label><input type="checkbox" wire:model.lazy="form.newsletter" <?= $form->newsletter ? 'checked' : '' ?> /> Subscribe to newsletter</label>
            <label><input type="checkbox" wire:model.lazy="form.terms" <?= $form->terms ? 'checked' : '' ?> /> I accept terms</label>
            <?php if (!empty($errors['terms'])): ?>
            <small class="error"><?= e($errors['terms']) ?></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="flex gap mt">
            <button type="button" wire:click="back" <?= $step===1 ? 'disabled' : '' ?>>Back</button>
            <?php if ($step < 3): ?>
            <button type="button" wire:click="next">Next</button>
            <?php else: ?>
            <button type="button" wire:click="submit">Create account</button>
            <?php endif; ?>
        </div>
    </form>
</div>