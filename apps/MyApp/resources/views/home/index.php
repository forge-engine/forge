<?php

use Forge\Http\Session;
use Forge\Enums\FlashMessageType;

/**
 * @var string $title
 * @var array $users
 * @var Session $session
 */

?>
<!DOCTYPE html>
<html>

<head>
    <title><?= $title ?></title>
</head>

<body>
<h1>Welcome to Forge <?= $users['name'] ?>!</h1>

<?php if ($session->hasFlash(FlashMessageType::SUCCESS)): ?>
    <div class="success"><?= $session->getFlash(FlashMessageType::SUCCESS) ?></div>
<?php endif; ?>
</body>

</html>