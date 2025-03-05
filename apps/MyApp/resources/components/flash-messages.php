<?php

use Forge\Http\Session;

/**
 * @var Session $session
 */

?>
<div class="flash-messages">
    <?php $flashMessages = $session->getFlashMessages(); ?>
    <?php if (!empty($flashMessages) && is_array($flashMessages)): ?>
        <?php foreach ($flashMessages as $type => $message): ?>
            <div class="flash-message flash-message-<?= htmlspecialchars($type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>