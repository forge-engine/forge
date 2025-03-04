<?php

use Forge\Http\Session;
use Forge\Enums\FlashMessageType;

/**
 * @var Session $session
 */

?>
<div class="container">
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

    <form action="/flash-message-test" method="POST" class="form-wrapper">
        <div class="form-group"><label for="name">Name:</label> <input type="text" name="name" id="name" required>
        </div>
        <div class="form-group"><label for="lastName">Last Name:</label>
            <input type="text" name="lastName" id="lastname" required>
        </div>
        <button type="submit" class="button primary">Submit</button>
    </form>
</div>