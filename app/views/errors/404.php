<?php

use Forge\Core\View\Component;
layout("main");

$path = $_SERVER["REQUEST_URI"];
?>

<div class="container">
    <h1>404 - Page Not Found</h1>
    <p>The requested resource could not be located</p>
    <?php Component::render("alert", [
        "type" => "danger",
        "message" => "Path: $path",
    ]); ?>
</div>
