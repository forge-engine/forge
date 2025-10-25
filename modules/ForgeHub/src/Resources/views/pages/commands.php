<?php
layout(name: "main");

$commandHistory = $_SESSION['command_history'] ?? [];
?>
<div class="mt-sm">
    <h1 class="mb-sm">Forge Framework Web CLI</h1>

    <div class="card mb-sm">
        <div class="card--body">
            <div class="terminal">
                <div class="terminal__bar">
                    <?= htmlspecialchars($whoami ?? 'user') ?> - <?= htmlspecialchars($pwd ?? '/') ?>
                </div>
                <div class="terminal__output" id="cliOutput">
                    <?php if (isset($command)): ?>
                        <div class="command-line">
                            $ <?= htmlspecialchars($command) ?>
                        </div>
                        <pre><?= $output ?? '' ?></pre>
                        <?php if ($needsInput && isset($processId)): ?>
                            <form method="post" action="/hub/commands/send-input" class="form flex flex--gap-sm mt-sm">
                                <div class="form--group flex-grow">
                                    <label for="input" class="form--label"><?= htmlspecialchars($prompt) ?></label>
                                    <input type="text" name="input" id="input" class="form--input"
                                           placeholder="Enter your response" autocomplete="off">
                                </div>
                                <input type="hidden" name="process_id" value="<?= htmlspecialchars($processId) ?>">
                                <button type="submit" class="button button--secondary">Send Input</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="terminal__persistent-commands">
                    <?php if (!empty($persistentCommands)): ?>
                        <p>Persistent Commands:</p>
                        <ul>
                            <?php foreach ($persistentCommands as $pCommand): ?>
                                <li><?= htmlspecialchars($pCommand) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <form method="post" action="/hub/commands/execute" class="form flex flex--gap-sm terminal__input"
                      id="commandForm">
                    <label for="command" class="form--label sr-only">$</label>
                    <input type="text" name="command" id="command" class="form--input" placeholder="Enter Forge command"
                           autocomplete="off">
                    <button type="submit" class="button">Execute</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .terminal {
        border: 1px solid var(--color-border);
        border-radius: var(--radius-base);
        background-color: var(--color-neutral-900);
        color: var(--color-neutral-100);
        padding: var(--space-sm);
        font-family: monospace;
        display: flex;
        flex-direction: column;
        height: 90vh;
        /* Adjust as needed */
    }

    .terminal__bar {
        border-bottom: 1px solid var(--color-border);
        padding: var(--space-xxs);
        margin-bottom: var(--space-xs);
        font-size: var(--text-sm);
    }

    .terminal__output {
        flex-grow: 1;
        overflow-y: auto;
        padding-bottom: var(--space-sm);
        line-height: 1.4;
    }

    .terminal__input {
        margin-top: var(--space-sm);
        gap: var(--space-xs);
        border-top: 1px solid var(--color-border);
        padding-top: var(--space-xs);
    }

    .terminal__input .form-group {
        flex-grow: 1;
    }

    .terminal__output pre {
        /* To style the output within the pre tag */
        white-space: pre-wrap;
        margin: 0;
        font-family: monospace;
        font-size: var(--text-sm);
    }

    .command-line {
        color: var(--color-accent);
        margin-bottom: var(--space-xxs);
        font-weight: bold;
    }

    .user-input {
        color: var(--color-success);
        margin-bottom: var(--space-xxs);
        font-style: italic;
    }

    .loading-indicator {
        color: var(--color-neutral-300);
        font-style: italic;
        margin: var(--space-xxs) 0;
    }

    .error-message {
        color: var(--color-error);
        margin: var(--space-xxs) 0;
    }

    .terminal__persistent-commands {
        margin-top: var(--space-sm);
        padding: var(--space-xs);
        border-top: 1px solid var(--color-border);
        font-size: var(--text-sm);
        color: var(--color-neutral-300);
        /* A lighter color for less important info */
    }

    .terminal__persistent-commands ul {
        list-style-type: none;
        padding-left: var(--space-unit);
        margin-bottom: 0;
    }

    .terminal__persistent-commands li {
        margin-bottom: var(--space-xxs);
    }

    /* Disable the command input when a command is running */
    #command:disabled {
        background-color: var(--color-neutral-800);
        color: var(--color-neutral-400);
        cursor: not-allowed;
    }

    /* Style for the input form that appears in the terminal output */
    .terminal__output form {
        margin-top: var(--space-xs);
        background-color: var(--color-neutral-800);
        padding: var(--space-xs);
        border-radius: var(--radius-sm);
    }

    .terminal__output form .form--label {
        color: var(--color-accent);
        font-weight: bold;
    }
</style>
<script>
    const commandInput = document.getElementById('command');
    const commandForm = document.getElementById('commandForm');
    const cliOutput = document.getElementById('cliOutput');
    let historyIndex = -1;
    const commandHistory = <?php echo json_encode(array_reverse($commandHistory)); ?>; // Load history from session
    let tempInput = '';
    let activeProcessId = null;
    let pollInterval = null;

    commandInput.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (historyIndex < commandHistory.length - 1) {
                if (historyIndex === -1) {
                    tempInput = commandInput.value;
                }
                historyIndex++;
                commandInput.value = commandHistory[historyIndex];
            }
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (historyIndex > -1) {
                historyIndex--;
                if (historyIndex === -1) {
                    commandInput.value = tempInput;
                } else {
                    commandInput.value = commandHistory[historyIndex];
                }
            }
        } else if (event.key === 'Enter') {
            event.preventDefault();
            historyIndex = -1; // Reset history index on new command
            tempInput = '';
            submitCommandForm();
        }
    });

    // Handle command form submission via AJAX
    commandForm.addEventListener('submit', function (event) {
        event.preventDefault();
        submitCommandForm();
    });

    // Handle input form submissions via event delegation
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (form.action && form.action.includes('/hub/commands/send-input')) {
            event.preventDefault();
            submitInputForm(form);
        }
    });

    function submitCommandForm() {
        const command = commandInput.value.trim();
        if (!command) return;

        // Add command to output display
        const cmdLineDiv = document.createElement('div');
        cmdLineDiv.className = 'command-line';
        cmdLineDiv.textContent = '$ ' + command;
        cliOutput.appendChild(cmdLineDiv);

        // Create loading indicator
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading-indicator';
        loadingDiv.textContent = 'Executing command...';
        cliOutput.appendChild(loadingDiv);

        // Scroll to bottom
        cliOutput.scrollTop = cliOutput.scrollHeight;

        // Clear input
        commandInput.value = '';

        // Submit form via fetch
        const formData = new FormData(commandForm);
        formData.set('command', command);

        fetch('/hub/commands/execute', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                // Remove loading indicator
                if (loadingDiv.parentNode) {
                    loadingDiv.parentNode.removeChild(loadingDiv);
                }

                activeProcessId = data.processId;
                updateUI(data);

                // Start polling for updates if the process is still running or waiting for input
                if (data.status === 'running' || data.status === 'waiting_for_input') {
                    startPolling(activeProcessId);
                }
            })
            .catch(error => {
                console.error('Error executing command:', error);
                loadingDiv.textContent = 'Error: ' + error.message;
                loadingDiv.className = 'error-message';
            });
    }

    function submitCommandForm() {
        const command = commandInput.value.trim();
        if (!command) return;

        // Add command to output display
        const cmdLineDiv = document.createElement('div');
        cmdLineDiv.className = 'command-line';
        cmdLineDiv.textContent = '$ ' + command;
        cliOutput.appendChild(cmdLineDiv);

        // Create loading indicator
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading-indicator';
        loadingDiv.textContent = 'Executing command...';
        cliOutput.appendChild(loadingDiv);

        // Scroll to bottom
        cliOutput.scrollTop = cliOutput.scrollHeight;

        // Clear input
        commandInput.value = '';

        // Submit form via fetch
        const formData = new FormData(commandForm);
        formData.set('command', command);

        fetch('/hub/commands/execute', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                // Remove loading indicator
                if (loadingDiv.parentNode) {
                    loadingDiv.parentNode.removeChild(loadingDiv);
                }

                activeProcessId = data.processId;

                let preElement = cliOutput.querySelector('pre');
                if (!preElement) {
                    preElement = document.createElement('pre');
                    cliOutput.appendChild(preElement);
                }
                preElement.innerHTML = data.data.output; // Try accessing output via data.data.output

                // updateUI(data); // Commented out

                // Start polling for updates if the process is still running or waiting for input
                if (data.status === 'running' || data.status === 'waiting_for_input') {
                    startPolling(activeProcessId);
                }
            })
            .catch(error => {
                console.error('Error executing command:', error);
                loadingDiv.textContent = 'Error: ' + error.message;
                loadingDiv.className = 'error-message';
            });
    }

    function startPolling(processId) {
        // Clear any existing polling
        if (pollInterval) {
            clearInterval(pollInterval);
        }
        pollCommandStatus(processId);
    }

    function pollCommandStatus(processId) {
        const pollIntervalTime = 1000; // 1 second

        pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`/hub/commands/status?process_id=${processId}`);
                const data = await response.json();

                if (data.status === 'completed' || data.status === 'error' || data.status === 'timeout') {
                    clearInterval(pollInterval);
                    updateUI(data);
                    commandInput.disabled = false;
                    commandInput.focus();
                } else if (data.status === 'waiting_for_input' || data.status === 'running') {
                    updateUI(data);
                    commandInput.disabled = true;
                }
            } catch (error) {
                console.error('Error polling command status:', error);
                clearInterval(pollInterval);
            }
        }, pollIntervalTime);
    }

    function updateUI(data) {
        // Update output
        if (data.output) {
            let preElement = cliOutput.querySelector('pre');
            if (!preElement) {
                preElement = document.createElement('pre');
                cliOutput.appendChild(preElement);
            }
            preElement.innerHTML = data.output;
        }

        // Handle input form
        const existingForm = cliOutput.querySelector('form[action="/hub/commands/send-input"]');
        if (existingForm) {
            cliOutput.removeChild(existingForm);
        }

        if (data.needsInput && data.prompt && data.processId) {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = '/hub/commands/send-input';
            form.className = 'form flex flex--gap-sm mt-sm';

            const formGroup = document.createElement('div');
            formGroup.className = 'form--group flex-grow';

            const label = document.createElement('label');
            label.htmlFor = 'input';
            label.className = 'form--label';
            label.textContent = data.prompt;

            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'input';
            input.id = 'input';
            input.className = 'form--input';
            input.placeholder = 'Enter your response';
            input.autocomplete = 'off';

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'process_id';
            hiddenInput.value = data.processId;

            const button = document.createElement('button');
            button.type = 'submit';
            button.className = 'button button--secondary';
            button.textContent = 'Send Input';

            formGroup.appendChild(label);
            formGroup.appendChild(input);
            form.appendChild(formGroup);
            form.appendChild(hiddenInput);
            form.appendChild(button);

            cliOutput.appendChild(form);
            input.focus();
        }

        // Scroll to bottom
        cliOutput.scrollTop = cliOutput.scrollHeight;
    }

    // Keep the output scrolled to the bottom
    if (cliOutput) {
        cliOutput.scrollTop = cliOutput.scrollHeight;
    }

    // Focus the input field on page load
    commandInput.focus();

    // If there's a process ID in the view (from a previous interactive command), start polling
    const initialProcessId = "<?= htmlspecialchars($processId ?? '') ?>";
    if (initialProcessId) {
        activeProcessId = initialProcessId;
        startPolling(activeProcessId);
    }
</script>