<div class="p-6 space-y-6">
    <h2 class="text-2xl font-semibold">Forge Benchmark</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($results as $label => $time): ?>
            <div class="p-4 bg-white rounded-xl shadow text-center flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-medium capitalize mb-2">
                        <?= str_replace("_", " ", $label) ?>
                    </h3>
                    <?php if ($time !== null): ?>
                        <div class="text-3xl font-bold text-blue-600"><?= $time ?> ms</div>
                    <?php else: ?>
                        <div class="text-gray-400">–</div>
                    <?php endif; ?>
                </div>

                <button
                    wire:click="run('<?= $label ?>')"
                    class="mt-4 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="run('<?= $label ?>')">Run</span>
                    <span wire:loading wire:target="run('<?= $label ?>')">Running...</span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>
