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
        <input class="form--input mb-sm" type="text" name="identifier" placeholder="identifier" required>
        <input class="form--input" type="password" name="password" placeholder="Password" required>
        <button class="button mt-sm" type="submit">Login</button>
        <?= form_close() ?>
    </section>
</section>