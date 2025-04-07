<?php
use App\Modules\ForgeAuth\Dto\UserDto;

/**
 * @var string $title
 * @var string $message
 * @var UserDto $user
 */

layout("main");
?>
<section class="container">
    <h2>Engine Status</h2>
    <ul>
        <li>PHP Version <?= PHP_VERSION ?></li>
        <li>Database Driver: <?= $_ENV["DB_DRIVER"] ?></li>
    </ul>

    <h3>User information</h3>
    <pre>
    <?php print_r($user); ?>
    </pre>

    <?= component("flash-message")?>

    <section>
        <form action="" method="POST" class="form">
            <div class="grid grid--2 mb-sm">
                <input class="form--input" type="text" name="username" placeholder="Username" required>
                <input class="form--input" type="password" name="password" placeholder="Password" required>
            </div>
            <input class="form--input mb-sm" type="email" name="email" placeholder="Email" required>
            <button class="button" type="submit">Create User</button>
        </form>
    </section>
    <?php if ($user):?>
    <form action="/<?= $user->id ?>" method="POST" class="form">
        <input type="hidden" name="_method" value="PATCH">
        <input class="form--input" type="text" name="username" placeholder="Username" value="<?= $user->username ?>" required>
        <input type="email" name="email" placeholder="Email" value="<?= $user->email ?>" required>
        <button class="button" type="submit">Update User</button>
    </form>
    <?php endif; ?>

    </div>