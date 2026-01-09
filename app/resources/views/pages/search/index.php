<?php layout('main') ?>

<div <?= fw_id('calc-id') ?>>
    <div fw:target>
        <input type="number" fw:model.defer="number1" value="<?= e($number1) ?>">
        <input type="number" fw:model.defer="number2" value="<?= e($number2) ?>">
        <button fw:click="calculate">Sum</button>
        <br /> <br />
        total: <?= $total ?>
    </div>
</div>

<div <?= fw_id('search-id') ?>>
    <h1>Search Demo</h1>
    <input type="search" fw:model.debounce="query" value="<?= e($query) ?>" placeholder="Type to search...">

    <div fw:target>
        <?= component(name: 'ui/query', props: ['query' => $query]) ?>
        <?php if (empty($results)) { ?>
            <p>No results yet.</p>
        <?php } else { ?>
            <ul>
                <?php foreach ($results as $item) { ?>
                    <li><?= $item->title ?></li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>

    <div fw:loading>
        Loading...
    </div>
</div>