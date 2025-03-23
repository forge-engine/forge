<?php
use Forge\Core\View\Component;

/**
 * @var string $title
 */

layout("main");
?>

<h2>Welcome <?=$title?></h2>

<?= Component::render("alert", ["type" => "success","children" => "Success message"])?>