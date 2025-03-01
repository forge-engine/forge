<?php
/** @var array $links */
?>
<nav>
    <ul class="forge-links-list">
        <?php foreach ($links as $link): ?>
            <li><a href="<?= $link['url'] ?>"><?= $link['label'] ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>