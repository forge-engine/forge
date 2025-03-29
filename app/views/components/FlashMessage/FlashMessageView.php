<?php
use Forge\Core\View\Component;
use Forge\Core\Helpers\Flash;

$flashMessages = Flash::flat();
?>
<?php if (!empty($flatMessages)): ?>
    <div>
        <?php foreach ($flatMessages as $msg): ?>
            <?=
                Component::render("alert", [
                    "type" => $msg["type"],
                    "children" => $msg["message"]
                ])
            ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>