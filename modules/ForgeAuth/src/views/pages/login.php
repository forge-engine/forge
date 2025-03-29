<?php
use App\Dto\UserDto;

/**
 * @var string $title
 * @var string $message
 * @var UserDto $user
 */

layout("main");
?>
<section class="container">
    <h2>Login Administration</h2>

    <?= component("flash-message")?>

    <section>
        <form action="" method="POST" class="form">
            <input class="form--input mb-sm" type="email" name="email" placeholder="Email" required>
            <input class="form--input" type="password" name="password" placeholder="Password" required>
            <button class="button mt-sm" type="submit">Login</button>
        </form>
    </section>

    </div>