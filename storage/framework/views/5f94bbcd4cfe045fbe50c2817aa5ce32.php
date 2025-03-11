<?php

use App\Dto\UserDto;

/**
 * @var string $title
 * @var string $message
 * @var UserDto $user
 */
?>

<?php layout('main'); ?>

<?php \Forge\Core\View\View::startSection('title'); ?>
<?= e($title) ?>
<?php \Forge\Core\View\View::endSection(); ?>

<h2>Engine Status</h2>
<ul>
    <li>PHP Version <?= e(PHP_VERSION) ?></li>
    <li>Database Driver: SQLite</li>
</ul>

<h3>User information</h3>
<pre>
<?php
print_r($user);
?>
</pre>

<?= e($title) ?>

<?php echo \Forge\Core\View\Component::render('alert', ['type' => 'info', 'children' => 'This is a secure PHP framework'] ?? []); ?>
<?php // End component ?>