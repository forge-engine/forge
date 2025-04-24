<?php

use App\Modules\ForgeAuth\Models\User;
use Forge\Core\View\View;

/**
 * @var string $title
 * @var string $message
 * @var User $user
 */

View::layout(name: "main", loadFromModule: false);
?>
<section class="container">
    <h2>User area</h2>

    <h3>Welcome
        <form action="/auth/logout" method="POST">
            <button>Logout</button>
        </form>
    </h3>
    <p>Identifier: <?= $user->identifier ?></p>
    <p>Email: <?= $user->email ?></p>
    <p>Account created on: <?= $user->created_at ?></p>
</section>