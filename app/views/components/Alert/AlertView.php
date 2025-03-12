<?php
use App\View\Components\Alert\AlertPropsDto;
/** @var AlertPropsDto $alert */
?>

<div class="alert alert-<?= $alert->type ?? "info" ?>">
    <strong><?= $alert->type ?? "Alert" ?></strong>
<?= $alert->children ?>
</div>

<style>
    .alert {
        border: 1px solid #ccc;
        padding: 10px;
        margin: 10px 0;
    }
    .alert-success {
        border-color: #28a745;
        color: #28a745;
    }
    .alert-danger {
        border-color: #dc3545;
        color: #dc3545;
    }
    .alert-info {
        border-color: #17a2b8;
        color: #17a2b8;
    }
</style>
