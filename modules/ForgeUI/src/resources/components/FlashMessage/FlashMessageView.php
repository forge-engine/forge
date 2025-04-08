<?php
use Forge\Core\View\Component;
use Forge\Core\Helpers\Flash;

$flashMessages = Flash::flat() ?? [];
?>
<?php if (!empty($flashMessages)): ?>
<div>
    <?php foreach ($flashMessages as $msg): ?>
    <?=
                Component::render(name: "forge-ui:alert", props: [
                    "type" => $msg["type"],
                    "children" => $msg["message"]
                ], loadFromModule: true)
            ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>