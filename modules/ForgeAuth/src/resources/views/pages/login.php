<?php
use App\Dto\UserDto;
use Forge\Core\View\Component;
use Forge\Core\View\View;

/**
 * @var string $title
 * @var string $message
 * @var UserDto $user
 */

View::layout(name: "main", loadFromModule: false);
?>
<section class="container">
    <h2>Login Administration</h2>

    <?= Component::render(name: "forge-ui:flash-message", loadFromModule: true)?>

    <section>
        <form action="" method="POST" class="form">
            <?= csrf_input() ?>
            <input class="form--input mb-sm" type="text" name="identifier" placeholder="identifier" required>
            <input class="form--input" type="password" name="password" placeholder="Password" required>
            <button class="button mt-sm" type="submit">Login</button>
        </form>
    </section>

    </div>