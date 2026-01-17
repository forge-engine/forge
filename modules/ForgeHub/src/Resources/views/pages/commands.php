<?php
layout(name: "hub", fromModule: true, moduleName: "ForgeHub");

$commandHistory = $_SESSION['command_history'] ?? [];
?>
<div class="grid gap-6">
  <div class="bg-gray-900 rounded-lg shadow-sm overflow-hidden flex flex-col" style="height: 600px;">
    <div class="bg-gray-800 px-4 py-3 border-b border-gray-700 flex items-center justify-between">
      <div class="text-sm text-gray-300 font-mono flex items-center gap-2">
        <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd" />
        </svg>
        <span><?= htmlspecialchars($whoami ?? 'user') ?> - <?= htmlspecialchars($pwd ?? '/') ?></span>
      </div>
      <div class="text-xs text-gray-500">Forge CLI</div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 font-mono text-sm text-gray-100" id="cliOutput">
      <?php if (isset($command)): ?>
        <div class="text-green-400 mb-2 font-semibold">
          $ <?= htmlspecialchars($command) ?>
        </div>
        <pre class="whitespace-pre-wrap text-gray-100"><?= htmlspecialchars($output ?? '') ?></pre>
        <?php if ($needsInput && isset($processId)): ?>
          <form method="post" action="/hub/commands/send-input" class="mt-4 flex gap-2" id="inputForm">
            <label for="input" class="text-yellow-400 font-semibold"><?= htmlspecialchars($prompt) ?></label>
            <input type="text" name="input" id="input"
              class="flex-1 bg-gray-800 text-gray-100 border border-gray-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter your response" autocomplete="off">
            <input type="hidden" name="process_id" value="<?= htmlspecialchars($processId) ?>">
            <?= component(name: 'ForgeHub:button', props: ['type' => 'submit', 'variant' => 'primary', 'size' => 'sm', 'children' => 'Send', 'class' => 'bg-blue-600 hover:bg-blue-700']) ?>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <form method="post" action="/hub/commands/execute" class="border-t border-gray-700 p-4 bg-gray-800 flex gap-2"
      id="commandForm">
      <span class="text-green-400 font-mono font-semibold pt-2">$</span>
      <input type="text" name="command" id="command"
        class="flex-1 bg-gray-900 text-gray-100 border border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
        placeholder="Enter Forge command" autocomplete="off">
      <?= component(name: 'ForgeHub:button', props: ['type' => 'submit', 'variant' => 'primary', 'children' => 'Execute', 'class' => 'bg-blue-600 hover:bg-blue-700']) ?>
    </form>
  </div>
</div>

<script>
  const commandInput = document.getElementById('command');
  const commandForm = document.getElementById('commandForm');
  const cliOutput = document.getElementById('cliOutput');
  let historyIndex = -1;
  const commandHistory = <?php echo json_encode(array_reverse($commandHistory)); ?>;
  let tempInput = '';
  let activeProcessId = null;
  let pollInterval = null;

  commandInput?.addEventListener('keydown', (event) => {
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
      historyIndex = -1;
      tempInput = '';
      submitCommandForm();
    }
  });

  commandForm?.addEventListener('submit', function (event) {
    event.preventDefault();
    submitCommandForm();
  });

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

    const cmdLineDiv = document.createElement('div');
    cmdLineDiv.className = 'text-green-400 mb-2 font-semibold';
    cmdLineDiv.textContent = '$ ' + command;
    cliOutput.appendChild(cmdLineDiv);

    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'text-gray-400 italic mb-2';
    loadingDiv.textContent = 'Executing command...';
    cliOutput.appendChild(loadingDiv);

    cliOutput.scrollTop = cliOutput.scrollHeight;
    commandInput.value = '';
    commandInput.disabled = true;

    const formData = new FormData(commandForm);
    formData.set('command', command);

    fetch('/hub/commands/execute', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (loadingDiv.parentNode) {
          loadingDiv.parentNode.removeChild(loadingDiv);
        }

        activeProcessId = data.processId;

        let preElement = cliOutput.querySelector('pre');
        if (!preElement) {
          preElement = document.createElement('pre');
          preElement.className = 'whitespace-pre-wrap text-gray-100';
          cliOutput.appendChild(preElement);
        }
        preElement.textContent = data.output || '';

        if (data.status === 'running' || data.status === 'waiting_for_input') {
          startPolling(activeProcessId);
        } else {
          commandInput.disabled = false;
          commandInput.focus();
        }
      })
      .catch(error => {
        console.error('Error executing command:', error);
        loadingDiv.textContent = 'Error: ' + error.message;
        loadingDiv.className = 'text-red-400';
        commandInput.disabled = false;
      });
  }

  function submitInputForm(form) {
    const formData = new FormData(form);
    const input = form.querySelector('input[name="input"]');
    const inputValue = input.value;

    const userInputDiv = document.createElement('div');
    userInputDiv.className = 'text-yellow-400 mb-2 font-semibold';
    userInputDiv.textContent = inputValue;
    cliOutput.appendChild(userInputDiv);

    input.value = '';

    fetch('/hub/commands/send-input', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        form.remove();

        let preElement = cliOutput.querySelector('pre');
        if (!preElement) {
          preElement = document.createElement('pre');
          preElement.className = 'whitespace-pre-wrap text-gray-100';
          cliOutput.appendChild(preElement);
        }
        preElement.textContent = data.output || '';

        if (data.needsInput && data.prompt && data.processId) {
          createInputForm(data.prompt, data.processId);
        } else {
          commandInput.disabled = false;
          commandInput.focus();
          if (pollInterval) {
            clearInterval(pollInterval);
          }
        }
      })
      .catch(error => {
        console.error('Error sending input:', error);
      });
  }

  function createInputForm(prompt, processId) {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/hub/commands/send-input';
    form.className = 'mt-4 flex gap-2';

    const label = document.createElement('label');
    label.className = 'text-yellow-400 font-semibold pt-2';
    label.textContent = prompt;

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'input';
    input.className = 'flex-1 bg-gray-800 text-gray-100 border border-gray-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500';
    input.placeholder = 'Enter your response';
    input.autocomplete = 'off';

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'process_id';
    hiddenInput.value = processId;

    const button = document.createElement('button');
    button.type = 'submit';
    button.className = 'px-4 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium';
    button.textContent = 'Send';

    form.appendChild(label);
    form.appendChild(input);
    form.appendChild(hiddenInput);
    form.appendChild(button);

    cliOutput.appendChild(form);
    input.focus();
  }

  function startPolling(processId) {
    if (pollInterval) {
      clearInterval(pollInterval);
    }
    pollCommandStatus(processId);
  }

  function pollCommandStatus(processId) {
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
    }, 1000);
  }

  function updateUI(data) {
    if (data.output !== undefined) {
      let preElement = cliOutput.querySelector('pre');
      if (!preElement) {
        preElement = document.createElement('pre');
        preElement.className = 'whitespace-pre-wrap text-gray-100';
        cliOutput.appendChild(preElement);
      }
      preElement.textContent = data.output;
    }

    const existingForm = cliOutput.querySelector('form[action="/hub/commands/send-input"]');
    if (existingForm) {
      existingForm.remove();
    }

    if (data.needsInput && data.prompt && data.processId) {
      createInputForm(data.prompt, data.processId);
    }

    cliOutput.scrollTop = cliOutput.scrollHeight;
  }

  if (cliOutput) {
    cliOutput.scrollTop = cliOutput.scrollHeight;
  }

  commandInput?.focus();

  const initialProcessId = "<?= htmlspecialchars($processId ?? '') ?>";
  if (initialProcessId) {
    activeProcessId = initialProcessId;
    startPolling(activeProcessId);
  }
</script>
