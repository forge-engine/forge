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
    <h2>User area</h2>

    <h3>Welcome <?=$user->username?> <form action="/auth/logout" method="POST"><button>Logout</button></form>
    </h3>
    <p>Email: <?=$user->email?></p>
    <p>Account created on: <?=$user->created_at?->format('Y-m-d H:i')?></p>
</section>