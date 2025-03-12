<?php
use App\Dto\UserDto;
use Forge\Core\View\Component;

/**
 * @var string $title
 * @var string $message
 * @var UserDto $user
 */

layout("main");
?>

<h2>Engine Status</h2>
<ul>
    <li>PHP Version <?= e(PHP_VERSION) ?></li>
    <li>Database Driver: <?= e($_ENV["DB_DRIVER"]) ?></li>
</ul>

<h3>User information</h3>
<pre>
<?php print_r($user); ?>
</pre>

<form action="" method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="email" name="email" placeholder="Email" required>
    <button type="submit">Create User</button>
</form>

<form action="/<?= $user[0]->id ?>" method="POST">
    <input type="hidden" name="_method" value="PATCH">
    <input type="text" name="username" placeholder="Username" value="<?= $user[0]
        ->username ?>" required>
    <input type="email" name="email" placeholder="Email" value="<?= $user[0]
        ->email ?>" required>
    <button type="submit">Create User</button>
</form>

<?= Component::render("alert", [
    "type" => "success",
    "children" => "Success message",
]) ?>
