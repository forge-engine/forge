<?php

use App\Modules\ForgeAuth\Models\User;
use Forge\Core\View\Component;
use Forge\Core\View\View;

/**
 * @var string $title
 * @var string $message
 * @var User $user
 */

View::layout(name: "main", loadFromModule: false);
?>

<section class="container">
    <h2 class="text-2xl">Engine Status</h2>
    <ul>
        <li class="text-6xl">PHP Version <?= PHP_VERSION ?></li>
        <li>Database Driver: <?= $_ENV["DB_DRIVER"] ?></li>

        <h3>User information</h3>
        <pre>
    <?php print_r($user); ?>
    </pre>
        <section>
            <?= Component::render("forge-ui:alert", loadFromModule: true) ?>
            <form action="" method="POST" class="form">
                <?= csrf_input() ?>
                <div class="grid grid--2 mb-sm">
                    <input class="form--input" type="text" name="identifier" placeholder="Username" required>
                    <input class="form--input" type="password" name="password" placeholder="Password" required>
                </div>
                <input class="form--input mb-sm" type="email" name="email" autocomplete="email" placeholder="Email" required>
                <button class="button" type="submit">Create User</button>
            </form>
            <?php if ($user): ?>
            <form action="/<?= $user->id ?>" method="POST" class="form">
                <input type="hidden" name="_method" value="PATCH">
                <input class="form--input" type="text" name="identifier" placeholder="Username" value="<?= $user->identifier ?>" required>
                <input type="email" name="email" placeholder="Email" value="<?= $user->email ?>" required>
                <button class="button" type="submit">Update User</button>
            </form>
            <?php endif; ?>
        </section>

</section>