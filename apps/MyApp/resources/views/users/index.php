<?php
/**
 * @var array $data
 * @var \MyApp\Models\User[] $users
 */
$users = $data['users'];
?>

<h1>Users List</h1>

<ul>
    <?php foreach ($users as $user): ?>
        <li><?= $user->name ?> | <?= $user->email ?></li>
    <?php endforeach; ?>
</ul>