<?php

use Forge\Core\Helpers\Flash;

$flashMessages = Flash::flat() ?? [];
?>
<?php if (!empty($flashMessages)): ?>
    <div>
        <?php foreach ($flashMessages as $msg): ?>
            <?=
            component(name: "ForgeUi:Ui/Alert", props: [
                "type" => $msg["type"],
                "children" => $msg["message"]
            ], fromModule: true)
            ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>