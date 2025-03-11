<?php

use App\Dto\UserDto;

/**
 * @var string $title
 * @var string $message
 * @var UserDto $user
 */
?>

<?php layout('main'); ?>

@section('title')
<?= e($title) ?>
@endsection

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

{{$title}}

@component('alert', ['type' => 'info', 'children' => 'This is a secure PHP framework'])
@endcomponent