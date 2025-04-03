<?php
    layout("main");
?>
<div class="container mt-sm">
    <h1 class="mb-sm">Application Logs</h1>

    <div class="log-files mb-sm card">
        <?php foreach ($files as $file): ?>
        <a href="?file=<?= rawurlencode($file->getFilename()) ?>" class="button button--outline <?= ($_GET['file'] ?? '') === $file->getFilename() ? 'active' : '' ?>">
            <?= $file->getFilename() ?>
        </a>
        <?php endforeach; ?>
    </div>

    <form method="get" class="log-filters flex flex--gap-sm mb-sm">
        <input type="hidden" name="file" value="<?= $_GET['file'] ?? '' ?>" class="form--input">
        <input type="date" name="date" value="<?= $_GET['date'] ?? '' ?>" class="form--input">
        <input type="search" name="search" placeholder="Search messages..." value="<?= $_GET['search'] ?? '' ?>" class="form--input">
        <button type="submit" class="button">Filter</button>
    </form>

    <?php if (!empty($_GET['file'])): ?>
    <table class="log-entries">
        <thead>
            <tr>
                <th>Date</th>
                <th>Level</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr class="level-<?= strtolower($entry->level) ?>">
                <td><?= $entry->date->format('Y-m-d H:i:s') ?></td>
                <td><?= $entry->level ?></td>
                <td>
                    <p class="m-0"><?= $entry->message ?></p>
                    <?php if (!empty($entry->context)): ?>
                    <div class="log-context mt-sm">
                        <pre><?= json_encode($entry->context, JSON_PRETTY_PRINT) ?></pre>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>Please select a log file to view the entries.</p>
    <?php endif; ?>
</div>