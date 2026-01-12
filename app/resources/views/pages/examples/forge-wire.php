<?php layout('main') ?>

<div class="container my-5">
    <h1 class="text-3xl font-bold mb-5">ForgeWire Examples</h1>
    <p class="text-gray-600 mb-8">Comprehensive examples of all ForgeWire directives and modifiers</p>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">1. Polling Counter (Component with Slots)</h2>
        <?= component(
            name: 'examples/polling-counter',
            props: ['pollCount' => $pollCount],
            slots: [
                'header' => '<h3 class="text-xl font-semibold">Polling Counter - Auto-updates every 2 seconds</h3>',
                'help_text' => '<p class="text-sm text-gray-600 mb-3">This counter increments automatically on each poll using <code>fw:poll.2s</code></p>',
                'footer' => '<p class="text-xs text-gray-500">Uses <code>fw:target</code> for partial updates</p>'
            ]
        ) ?>
    </section>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">2. Interactive Counter (Component with Component Slots)</h2>
        <?= component(
            name: 'examples/interactive-counter',
            props: ['counter' => $counter, 'step' => $step],
            slots: [
                'header' => '<h3 class="text-xl font-semibold">Interactive Counter</h3>',
                'help_text' => component(
                    name: 'ui/alert',
                    props: ['type' => 'info', 'children' => 'Click buttons to increment/decrement. Use step input to change increment amount.']
                )
            ]
        ) ?>
    </section>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">3. Model Binding Demo (Component)</h2>
        <?= component(
            name: 'examples/model-binding',
            props: [
                'immediateValue' => $immediateValue,
                'lazyValue' => $lazyValue,
                'deferValue' => $deferValue,
                'debounceValue' => $debounceValue,
                'customDebounceValue' => $customDebounceValue
            ],
            slots: [
                'header' => '<h3 class="text-xl font-semibold">Model Binding Types</h3>',
                'help_text' => '<p class="text-sm text-gray-600 mb-3">Try typing in each input to see the different update behaviors</p>'
            ]
        ) ?>
    </section>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">4. Form Submission (Component with Slots)</h2>
        <?= component(
            name: 'examples/form-submission',
            props: [
                'formName' => $formName,
                'formEmail' => $formEmail,
                'formMessage' => $formMessage
            ],
            slots: [
                'header' => '<h3 class="text-xl font-semibold">Form with Validation</h3>',
                'help_text' => '<p class="text-sm text-gray-600 mb-3">Submit the form to see validation in action</p>',
                'submit_button' => '<button type="submit" class="btn btn-primary">Save Form</button>'
            ]
        ) ?>
    </section>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">5. Keydown Handler (Direct Island)</h2>
        <div <?= fw_id('keydown-demo') ?> class="card p-4 shadow-sm mb-4">
            <h3 class="text-xl font-semibold mb-3">Keydown Events</h3>
            <p class="text-sm text-gray-600 mb-4">Press Enter or Escape in the inputs below</p>
            
            <div class="space-y-4">
                <div>
                    <label class="block mb-2">Press Enter:</label>
                    <input type="text" fw:keydown.enter="handleEnter" class="form-control" placeholder="Type and press Enter" />
                </div>
                
                <div>
                    <label class="block mb-2">Press Escape:</label>
                    <input type="text" fw:keydown.escape="handleEscape" class="form-control" placeholder="Type and press Escape" />
                </div>
                
                <div fw:target class="mt-4 p-3 bg-gray-100 rounded">
                    <strong>Last Action:</strong> <?= e($lastKey) ?>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">6. Combined Features (Direct Island)</h2>
        <div <?= fw_id('combined-demo') ?> class="card p-4 shadow-sm mb-4">
            <h3 class="text-xl font-semibold mb-3">Combined Features Demo</h3>
            <p class="text-sm text-gray-600 mb-4">This island combines polling, click handlers, and model bindings</p>
            
            <div fw:poll.3s fw:action="onPoll" class="mb-4">
                <div fw:target>
                    <p class="text-sm text-gray-600">Auto-polling every 3 seconds. Poll count: <?= $pollCount ?></p>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2">Combined Value:</label>
                <input type="text" fw:model="combinedValue" value="<?= e($combinedValue) ?>" class="form-control" />
                <div fw:target class="mt-2 text-sm text-gray-600">
                    Value: <?= e($combinedValue) ?>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button fw:click="increment" class="btn btn-primary">Increment Counter</button>
                <button fw:click="decrement" class="btn btn-primary">Decrement Counter</button>
            </div>
            
            <div fw:target class="mt-4">
                <p><strong>Counter:</strong> <?= $counter ?></p>
            </div>
        </div>
    </section>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">7. Loading States (Direct Island)</h2>
        <div <?= fw_id('loading-demo') ?> class="card p-4 shadow-sm mb-4">
            <h3 class="text-xl font-semibold mb-3">Loading State Demo</h3>
            <p class="text-sm text-gray-600 mb-4">Click the button to see loading state (simulated 1 second delay)</p>
            
            <button fw:click="incrementLoading" class="btn btn-primary mb-3">Increment (with delay)</button>
            
            <div fw:target class="mb-3">
                <p><strong>Loading Counter:</strong> <?= $loadingCounter ?></p>
            </div>
            
            <div fw:loading class="text-info">
                Processing... Please wait
            </div>
        </div>
    </section>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">8. Shared State Demo</h2>
        <p class="mb-4 text-gray-600">These two islands share the same counter state using <code>#[State(shared: true)]</code>. Updating one updates both automatically.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div <?= fw_id('shared-counter-1') ?> class="card p-4 shadow-sm">
                <h3 class="text-lg font-semibold mb-2">Island 1</h3>
                <div class="flex gap-2 mb-3">
                    <button fw:click="incrementShared" class="btn btn-primary">+</button>
                    <button fw:click="decrementShared" class="btn btn-primary">-</button>
                </div>
                <div fw:target class="text-xl font-bold">
                    Shared Counter: <?= $sharedCounter ?>
                </div>
            </div>
            
            <div <?= fw_id('shared-counter-2') ?> class="card p-4 shadow-sm">
                <h3 class="text-lg font-semibold mb-2">Island 2</h3>
                <div class="flex gap-2 mb-3">
                    <button fw:click="incrementShared" class="btn btn-primary">+</button>
                    <button fw:click="decrementShared" class="btn btn-primary">-</button>
                </div>
                <div fw:target class="text-xl font-bold">
                    Shared Counter: <?= $sharedCounter ?>
                </div>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-blue-50 rounded">
            <p class="text-sm text-gray-700">
                <strong>Note:</strong> The counter above (non-shared) remains independent: 
                <span class="font-mono"><?= $counter ?></span>
            </p>
        </div>
    </section>

    <section class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">9. Polling with Target Updates (Direct Island)</h2>
        <div <?= fw_id('polling-target-demo') ?> class="card p-4 shadow-sm mb-4">
            <h3 class="text-xl font-semibold mb-3">Polling with fw:target</h3>
            <p class="text-sm text-gray-600 mb-4">This demonstrates polling that only updates the target section</p>
            
            <div class="mb-4">
                <p>Static content that doesn't update:</p>
                <p class="text-gray-500">This paragraph stays the same</p>
            </div>
            
            <div fw:poll.2s fw:action="onPoll">
                <div fw:target class="p-3 bg-gray-100 rounded">
                    <p><strong>Poll Count:</strong> <?= $pollCount ?></p>
                    <p><strong>Time:</strong> <?= date('H:i:s') ?></p>
                </div>
            </div>
        </div>
    </section>
</div>
