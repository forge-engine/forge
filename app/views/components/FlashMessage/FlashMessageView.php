<?php
use Forge\Core\View\Component;
use Forge\Core\Helpers\Flash;

$flashMessages = Flash::all();
?>
<?php if (!empty($flashMessages["error"])): ?>
    <div>
        <?php foreach ($flashMessages["error"] as $msg): ?>
        <?=
            Component::render("alert", ["type" => "error", "children" => $msg[0]])
        ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($flashMessages["success"])): ?>
    <div>
        <?=
            Component::render("alert", ["type" => "success", "children" => $flashMessages["success"]])
        ?>
    </div>
<?php endif; ?>