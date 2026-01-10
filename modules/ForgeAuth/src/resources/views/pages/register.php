<?php

use App\Modules\ForgeAuth\Dto\UserDto;

/**
 * @var string $title
 * @var string $message
 * @var UserDto $user
 */

layout(name: "main");
?>
<section class="container">
    <?= component(name: "ForgeUi:flash-message", fromModule: true) ?>
    <section>
        <?= form_open(attrs: ["class" => "form"]) ?>
        <input class="form--input mb-sm" type="text" name="identifier" placeholder="Identifier" required>
        <input class="form--input mb-sm" type="email" name="email" placeholder="Email" required>
        <input class="form--input mb-sm" type="password" name="password" placeholder="Password" required>
        <input class="form--input" type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button class="button mt-sm" type="submit">Register</button>
        <?= form_close() ?>
    </section>
</section>