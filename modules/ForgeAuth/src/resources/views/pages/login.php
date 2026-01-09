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
    <h2>Login Administration</h2>

    <?= component(name: "ForgeUi:flash-message", fromModule: true) ?>
    <section>
        <form action="" method="POST" class="form">
            <?= csrf_input() ?>
            <input class="form--input mb-sm" type="text" name="identifier" placeholder="identifier" required>
            <input class="form--input" type="password" name="password" placeholder="Password" required>
            <button class="button mt-sm" type="submit">Login</button>
        </form>
    </section>
</section>