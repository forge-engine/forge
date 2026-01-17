<?php

layout(name: "hub", fromModule: true, moduleName: "ForgeHub");
?>
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Deployment</h1>
      <p class="text-sm text-gray-500 mt-1">Manage and monitor your deployments</p>
    </div>
    <div class="flex items-center gap-2">
      <button id="refreshStatusBtn"
        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors flex items-center gap-2">
        <i class="fa-solid fa-rotate" id="refreshIcon"></i>
        <span>Refresh</span>
      </button>
      <button id="editConfigBtn"
        class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium transition-colors">
        Edit Config
      </button>
      <button id="manageSecretsBtn"
        class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium transition-colors">
        Manage Secrets
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Deployment Status</h2>
      <?php if ($status['has_state']): ?>
        <div class="space-y-4">
          <?php if ($status['server_ip']): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500">Server IP</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900" id="status-server-ip"><?= htmlspecialchars($status['server_ip']) ?></dd>
            </div>
          <?php endif; ?>
          <?php if ($status['domain']): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500">Domain</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900" id="status-domain"><?= htmlspecialchars($status['domain']) ?></dd>
            </div>
          <?php endif; ?>
          <div>
            <div class="flex items-center justify-between mb-2">
              <dt class="text-sm font-medium text-gray-500">Progress</dt>
              <dd class="text-sm font-semibold text-gray-900" id="status-progress"><?= htmlspecialchars((string) $status['progress_percentage']) ?>%</dd>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-blue-600 h-2 rounded-full transition-all" id="status-progress-bar"
                style="width: <?= htmlspecialchars((string) $status['progress_percentage']) ?>%"></div>
            </div>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 mb-2">Completed Steps</dt>
            <dd class="text-sm text-gray-900" id="status-completed-steps">
              <?php if (!empty($status['completed_steps'])): ?>
                <ul class="space-y-1">
                  <?php foreach ($status['completed_steps'] as $step): ?>
                    <li class="flex items-center gap-2">
                      <i class="fa-solid fa-check text-green-600"></i>
                      <span><?= htmlspecialchars($step) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <span class="text-gray-400">No steps completed yet</span>
              <?php endif; ?>
            </dd>
          </div>
          <?php if ($status['current_step']): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500">Current Step</dt>
              <dd class="mt-1 text-sm font-semibold text-blue-600" id="status-current-step"><?= htmlspecialchars($status['current_step']) ?></dd>
            </div>
          <?php endif; ?>
          <?php if ($status['last_updated']): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
              <dd class="mt-1 text-sm text-gray-900" id="status-last-updated"><?= htmlspecialchars($status['last_updated']) ?></dd>
            </div>
          <?php endif; ?>
          <div>
            <dt class="text-sm font-medium text-gray-500">Server Status</dt>
            <dd class="mt-1">
              <?php if ($status['is_accessible']): ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800" id="status-accessible">Accessible</span>
              <?php else: ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800" id="status-accessible">Not Accessible</span>
              <?php endif; ?>
            </dd>
          </div>
        </div>
      <?php else: ?>
        <div class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No deployment state</h3>
          <p class="mt-1 text-sm text-gray-500">Start a new deployment to see status here.</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Deployment Actions</h2>
      <div class="space-y-3">
        <button id="deployBtn"
          class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors flex items-center justify-center gap-2">
          <i class="fa-solid fa-rocket"></i>
          <span>Deploy</span>
        </button>
        <button id="deployAppBtn"
          class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium transition-colors flex items-center justify-center gap-2">
          <i class="fa-solid fa-upload"></i>
          <span>Deploy App</span>
        </button>
        <button id="updateBtn"
          class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium transition-colors flex items-center justify-center gap-2">
          <i class="fa-solid fa-arrow-up"></i>
          <span>Update</span>
        </button>
        <button id="rollbackBtn"
          class="w-full px-4 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-medium transition-colors flex items-center justify-center gap-2">
          <i class="fa-solid fa-undo"></i>
          <span>Rollback</span>
        </button>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Configuration</h2>
      <?php if ($has_config && $config): ?>
        <div class="space-y-3">
          <?php if (isset($config['server'])): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500">Server</dt>
              <dd class="mt-1 text-sm text-gray-900">
                <?php if (isset($config['server']['name'])): ?>
                  <span><?= htmlspecialchars($config['server']['name']) ?></span>
                <?php endif; ?>
                <?php if (isset($config['server']['region'])): ?>
                  <span class="text-gray-400"> • <?= htmlspecialchars($config['server']['region']) ?></span>
                <?php endif; ?>
              </dd>
            </div>
          <?php endif; ?>
          <?php if (isset($config['provision'])): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500">Provision</dt>
              <dd class="mt-1 text-sm text-gray-900">
                <?php if (isset($config['provision']['php_version'])): ?>
                  <span>PHP <?= htmlspecialchars($config['provision']['php_version']) ?></span>
                <?php endif; ?>
                <?php if (isset($config['provision']['database_type'])): ?>
                  <span class="text-gray-400"> • <?= htmlspecialchars($config['provision']['database_type']) ?></span>
                <?php endif; ?>
              </dd>
            </div>
          <?php endif; ?>
          <?php if (isset($config['deployment'])): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500">Deployment</dt>
              <dd class="mt-1 text-sm text-gray-900">
                <?php if (isset($config['deployment']['domain'])): ?>
                  <span><?= htmlspecialchars($config['deployment']['domain']) ?></span>
                <?php endif; ?>
              </dd>
            </div>
          <?php endif; ?>
          <?php if ($config_path): ?>
            <div class="pt-2 border-t border-gray-200">
              <dt class="text-sm font-medium text-gray-500">Config File</dt>
              <dd class="mt-1 text-xs text-gray-600 font-mono"><?= htmlspecialchars($config_path) ?></dd>
            </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-8">
          <p class="text-sm text-gray-500">No configuration found</p>
          <button onclick="editConfig()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">Create Configuration</button>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($has_config && $config && isset($config['deployment'])): ?>
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Post-Deployment Commands</h2>
          <button id="editPostCommandsBtn"
            class="px-3 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors">
            <i class="fa-solid fa-edit"></i> Edit
          </button>
        </div>
        <?php if (!empty($config['deployment']['post_deployment_commands'])): ?>
          <div class="space-y-2" id="postCommandsDisplay">
            <?php foreach ($config['deployment']['post_deployment_commands'] as $cmd): ?>
              <div class="flex items-center gap-2 p-2 bg-gray-50 rounded border border-gray-200">
                <i class="fa-solid fa-terminal text-gray-400 text-xs"></i>
                <span class="text-sm text-gray-900 font-mono"><?= htmlspecialchars($cmd) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <p class="text-sm text-gray-500">No post-deployment commands configured</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Environment Variables</h2>
          <button id="editEnvVarsBtn"
            class="px-3 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors">
            <i class="fa-solid fa-edit"></i> Edit
          </button>
        </div>
        <?php if (!empty($config['deployment']['env_vars'])): ?>
          <div class="space-y-2" id="envVarsDisplay">
            <?php foreach ($config['deployment']['env_vars'] as $key => $value): ?>
              <div class="flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                  <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($key) ?></span>
                  <span class="text-sm text-gray-400">=</span>
                  <span class="text-sm text-gray-900 font-mono truncate"><?= htmlspecialchars($value) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <p class="text-sm text-gray-500">No environment variables configured</p>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Deployments</h2>
      <?php if (!empty($recent_logs)): ?>
        <div class="space-y-2">
          <?php foreach ($recent_logs as $log): ?>
            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($log['id']) ?></div>
                <div class="text-xs text-gray-500"><?= date('M j, Y H:i:s', $log['modified']) ?></div>
              </div>
              <button onclick="viewLogs('<?= htmlspecialchars($log['id']) ?>')"
                class="ml-2 px-3 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded">
                View
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-8">
          <p class="text-sm text-gray-500">No deployment logs yet</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<script>
  let currentDeploymentId = null;

  document.getElementById('refreshStatusBtn')?.addEventListener('click', async function () {
    const button = this;
    const icon = document.getElementById('refreshIcon');
    const originalText = button.querySelector('span')?.textContent;

    button.disabled = true;
    if (icon) {
      icon.classList.add('fa-spin');
    }

    try {
      const response = await fetch('/hub/deployment/status', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        }
      });

      const data = await response.json();
      if (data.success) {
        updateStatus(data.status);
      }
    } catch (error) {
      console.error('Error refreshing status:', error);
    } finally {
      button.disabled = false;
      if (icon) {
        icon.classList.remove('fa-spin');
      }
    }
  });

  document.getElementById('deployBtn')?.addEventListener('click', () => executeDeployment('deploy'));
  document.getElementById('deployAppBtn')?.addEventListener('click', () => executeDeployment('deploy-app'));
  document.getElementById('updateBtn')?.addEventListener('click', () => executeDeployment('update'));
  document.getElementById('rollbackBtn')?.addEventListener('click', () => executeDeployment('rollback'));
  document.getElementById('editConfigBtn')?.addEventListener('click', () => editConfig());
  document.getElementById('manageSecretsBtn')?.addEventListener('click', () => manageSecrets());
  document.getElementById('editPostCommandsBtn')?.addEventListener('click', () => editPostCommands());
  document.getElementById('editEnvVarsBtn')?.addEventListener('click', () => editEnvVars());

  function updateStatus(status) {
    if (!status.has_state) {
      return;
    }

    if (status.server_ip) {
      const el = document.getElementById('status-server-ip');
      if (el) el.textContent = status.server_ip;
    }
    if (status.domain) {
      const el = document.getElementById('status-domain');
      if (el) el.textContent = status.domain;
    }
    if (status.progress_percentage !== undefined) {
      const el = document.getElementById('status-progress');
      const bar = document.getElementById('status-progress-bar');
      if (el) el.textContent = status.progress_percentage + '%';
      if (bar) bar.style.width = status.progress_percentage + '%';
    }
    if (status.current_step) {
      const el = document.getElementById('status-current-step');
      if (el) el.textContent = status.current_step;
    }
    if (status.last_updated) {
      const el = document.getElementById('status-last-updated');
      if (el) el.textContent = status.last_updated;
    }
    if (status.is_accessible !== undefined) {
      const el = document.getElementById('status-accessible');
      if (el) {
        el.textContent = status.is_accessible ? 'Accessible' : 'Not Accessible';
        el.className = status.is_accessible
          ? 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800'
          : 'px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800';
      }
    }
  }

  async function executeDeployment(type) {
    showConfirmation(
      'Confirm Deployment',
      `Are you sure you want to ${type}? This may take several minutes.`,
      () => {
        performDeployment(type);
      }
    );
  }

  async function performDeployment(type) {

    const endpoint = `/hub/deployment/${type}`;

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        },
        body: JSON.stringify({ args: {} })
      });

      const data = await response.json();
      if (data.success) {
        currentDeploymentId = data.deployment_id;
        showNotification('success', 'Deployment Started', `Deployment started successfully. ID: ${data.deployment_id}`);
        setTimeout(() => viewLogs(data.deployment_id), 1000);
      } else {
        showNotification('error', 'Deployment Failed', data.message || 'Unknown error occurred');
      }
    } catch (error) {
      showNotification('error', 'Error', 'Error starting deployment: ' + error.message);
    }
  }

  function editConfig() {
    const modal = document.getElementById('configModal');
    if (modal) {
      modal.classList.remove('hidden');
    }
  }

  function manageSecrets() {
    const modal = document.getElementById('secretsModal');
    if (modal) {
      modal.classList.remove('hidden');
    }
  }

  function editPostCommands() {
    const modal = document.getElementById('postCommandsModal');
    if (modal) {
      modal.classList.remove('hidden');
      loadPostCommands();
    }
  }

  function editEnvVars() {
    const modal = document.getElementById('envVarsModal');
    if (modal) {
      modal.classList.remove('hidden');
      loadEnvVars();
    }
  }

  function closePostCommandsModal() {
    const modal = document.getElementById('postCommandsModal');
    if (modal) {
      modal.classList.add('hidden');
    }
  }

  function closeEnvVarsModal() {
    const modal = document.getElementById('envVarsModal');
    if (modal) {
      modal.classList.add('hidden');
    }
  }

  async function loadPostCommands() {
    try {
      const response = await fetch('/hub/deployment/config', {
        headers: {
          'X-CSRF-Token': window.csrfToken || ''
        }
      });
      const data = await response.json();
      if (data.success && data.config?.deployment?.post_deployment_commands) {
        const textarea = document.getElementById('postCommandsTextarea');
        if (textarea) {
          textarea.value = data.config.deployment.post_deployment_commands.join('\n');
        }
      }
    } catch (error) {
      console.error('Error loading post-deployment commands:', error);
    }
  }

  async function loadEnvVars() {
    try {
      const response = await fetch('/hub/deployment/config', {
        headers: {
          'X-CSRF-Token': window.csrfToken || ''
        }
      });
      const data = await response.json();
      const container = document.getElementById('envVarsContainer');
      if (!container) return;

      container.innerHTML = '';

      if (data.success && data.config?.deployment?.env_vars) {
        Object.entries(data.config.deployment.env_vars).forEach(([key, value]) => {
          addEnvVarRow(key, value);
        });
      }

      if (container.children.length === 0) {
        addEnvVarRow();
      }
    } catch (error) {
      console.error('Error loading environment variables:', error);
    }
  }

  function addEnvVarRow(key = '', value = '') {
    const container = document.getElementById('envVarsContainer');
    if (!container) return;

    const row = document.createElement('div');
    row.className = 'flex items-center gap-2';
    row.innerHTML = `
      <input type="text" placeholder="Variable name" value="${key}"
        class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
        required>
      <span class="text-gray-400">=</span>
      <input type="text" placeholder="Variable value" value="${value}"
        class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
        required>
      <button type="button" onclick="removeEnvVarRow(this)"
        class="px-3 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors">
        <i class="fa-solid fa-trash"></i>
      </button>
    `;
    container.appendChild(row);
  }

  function removeEnvVarRow(button) {
    button.closest('div').remove();
  }

  document.getElementById('postCommandsForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const textarea = document.getElementById('postCommandsTextarea');
    const commands = textarea.value.split('\n').map(cmd => cmd.trim()).filter(cmd => cmd.length > 0);

    try {
      const response = await fetch('/hub/deployment/config', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        },
        body: JSON.stringify({
          update: 'post_deployment_commands',
          post_deployment_commands: commands
        })
      });

      const data = await response.json();
      if (data.success) {
        showNotification('success', 'Commands Saved', 'Post-deployment commands saved successfully');
        closePostCommandsModal();
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification('error', 'Save Failed', 'Failed to save commands: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      showNotification('error', 'Error', 'Error saving commands: ' + error.message);
    }
  });

  document.getElementById('envVarsForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const container = document.getElementById('envVarsContainer');
    const rows = container.querySelectorAll('div');
    const envVars = {};

    rows.forEach(row => {
      const inputs = row.querySelectorAll('input');
      if (inputs.length >= 2) {
        const key = inputs[0].value.trim();
        const value = inputs[1].value.trim();
        if (key) {
          envVars[key] = value;
        }
      }
    });

    try {
      const response = await fetch('/hub/deployment/config', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        },
        body: JSON.stringify({
          update: 'env_vars',
          env_vars: envVars
        })
      });

      const data = await response.json();
      if (data.success) {
        showNotification('success', 'Variables Saved', 'Environment variables saved successfully');
        closeEnvVarsModal();
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification('error', 'Save Failed', 'Failed to save variables: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      showNotification('error', 'Error', 'Error saving variables: ' + error.message);
    }
  });

  function viewLogs(deploymentId) {
    currentDeploymentId = deploymentId;
    const modal = document.getElementById('logsModal');
    if (modal) {
      modal.classList.remove('hidden');
      loadLogs(deploymentId);
    }
  }

  async function loadLogs(deploymentId) {
    const logsContent = document.getElementById('logsContent');
    if (!logsContent) return;

    try {
      const response = await fetch(`/hub/deployment/logs/${deploymentId}`, {
        method: 'GET',
        headers: {
          'X-CSRF-Token': window.csrfToken || ''
        }
      });

      const data = await response.json();
      if (data.success) {
        logsContent.textContent = data.logs || 'No logs available';
        logsContent.scrollTop = logsContent.scrollHeight;
      } else {
        logsContent.textContent = 'Failed to load logs: ' + (data.message || 'Unknown error');
      }
    } catch (error) {
      logsContent.textContent = 'Error loading logs: ' + error.message;
    }
  }
</script>

<div id="configModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeConfigModal()"></div>
  <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">Deployment Configuration</h3>
        <button onclick="closeConfigModal()"
          class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div class="px-6 py-6">
        <form id="configForm" class="space-y-6">
          <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Server Configuration</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Server Name</label>
                <input type="text" name="server[name]" id="serverName"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                <input type="text" name="server[region]" id="serverRegion"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                <input type="text" name="server[size]" id="serverSize"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <input type="text" name="server[image]" id="serverImage"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
            </div>
          </div>
          <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Provision Configuration</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PHP Version</label>
                <input type="text" name="provision[php_version]" id="phpVersion"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Type</label>
                <input type="text" name="provision[database_type]" id="databaseType"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Version</label>
                <input type="text" name="provision[database_version]" id="databaseVersion"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                <input type="text" name="provision[database_name]" id="databaseName"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
            </div>
          </div>
          <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Deployment Configuration</h4>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
                <input type="text" name="deployment[domain]" id="deploymentDomain"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SSL Email</label>
                <input type="email" name="deployment[ssl_email]" id="sslEmail"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
            </div>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeConfigModal()"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              Save Configuration
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="logsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeLogsModal()"></div>
  <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">Deployment Logs</h3>
        <div class="flex items-center gap-2">
          <button id="refreshLogsBtn" onclick="refreshLogs()"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
            <i class="fa-solid fa-rotate"></i> Refresh
          </button>
          <button onclick="closeLogsModal()"
            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
      <div class="px-6 py-6">
        <div id="logsContent"
          class="bg-gray-900 text-gray-100 font-mono text-sm p-4 rounded-lg max-h-96 overflow-y-auto whitespace-pre-wrap"
          style="min-height: 200px;">
          Loading logs...
        </div>
      </div>
    </div>
  </div>
</div>

<div id="secretsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeSecretsModal()"></div>
  <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">Manage Secrets</h3>
        <button onclick="closeSecretsModal()"
          class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div class="px-6 py-6">
        <form id="secretsForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">DigitalOcean API Token</label>
            <div class="flex items-center gap-2">
              <input type="password" name="digitalocean_api_token" id="digitaloceanToken"
                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                placeholder="Enter API token or leave blank to keep current">
              <button type="button" onclick="togglePassword('digitaloceanToken')"
                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
                <i class="fa-solid fa-eye" id="digitaloceanTokenIcon"></i>
              </button>
            </div>
            <p class="mt-1 text-xs text-gray-500">Leave blank to keep current value (masked as ••••••••)</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cloudflare API Token</label>
            <div class="flex items-center gap-2">
              <input type="password" name="cloudflare_api_token" id="cloudflareToken"
                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                placeholder="Enter API token or leave blank to keep current">
              <button type="button" onclick="togglePassword('cloudflareToken')"
                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
                <i class="fa-solid fa-eye" id="cloudflareTokenIcon"></i>
              </button>
            </div>
            <p class="mt-1 text-xs text-gray-500">Leave blank to keep current value (masked as ••••••••)</p>
          </div>
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start gap-2">
              <i class="fa-solid fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
              <div class="text-sm text-yellow-800">
                <p class="font-medium mb-1">Security Notice</p>
                <p>Secrets are stored securely. Only enter new values if you need to update them. Existing values are masked for security.</p>
              </div>
            </div>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeSecretsModal()"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              Save Secrets
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="postCommandsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closePostCommandsModal()"></div>
  <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="relative w-full max-w-3xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">Edit Post-Deployment Commands</h3>
        <button onclick="closePostCommandsModal()"
          class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div class="px-6 py-6">
        <form id="postCommandsForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Commands (one per line)</label>
            <textarea id="postCommandsTextarea" rows="10"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm font-mono"
              placeholder="cache:flush&#10;migrate&#10;queue:restart"></textarea>
            <p class="mt-1 text-xs text-gray-500">Enter one command per line. These commands will run after deployment completes.</p>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closePostCommandsModal()"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              Save Commands
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="envVarsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeEnvVarsModal()"></div>
  <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="relative w-full max-w-3xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">Edit Environment Variables</h3>
        <button onclick="closeEnvVarsModal()"
          class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div class="px-6 py-6">
        <form id="envVarsForm" class="space-y-4">
          <div id="envVarsContainer" class="space-y-3">
          </div>
          <button type="button" id="addEnvVarBtn" onclick="addEnvVarRow()"
            class="w-full px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
            <i class="fa-solid fa-plus mr-2"></i> Add Variable
          </button>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeEnvVarsModal()"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              Save Variables
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="notificationModal" class="hidden fixed inset-0 z-[60] overflow-y-auto pointer-events-none">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-transparent transition-opacity" aria-hidden="true"></div>
    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full pointer-events-auto"
      id="notificationContent">
      <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="sm:flex sm:items-start">
          <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10" id="notificationIcon">
          </div>
          <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="notificationTitle">
            </h3>
            <div class="mt-2">
              <p class="text-sm text-gray-500" id="notificationMessage">
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="button" id="notificationOkBtn"
          class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
          OK
        </button>
      </div>
    </div>
  </div>
</div>

<div id="confirmationModal" class="hidden fixed inset-0 z-[60] overflow-y-auto">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeConfirmationModal()"></div>
    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
      onclick="event.stopPropagation()">
      <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="sm:flex sm:items-start">
          <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
          </div>
          <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="confirmationTitle">
            </h3>
            <div class="mt-2">
              <p class="text-sm text-gray-500" id="confirmationMessage">
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="button" id="confirmationConfirmBtn"
          class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
          Confirm
        </button>
        <button type="button" id="confirmationCancelBtn"
          class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
          Cancel
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  function showNotification(type, title, message, autoClose = true) {
    const modal = document.getElementById('notificationModal');
    const content = document.getElementById('notificationContent');
    const icon = document.getElementById('notificationIcon');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    const okBtn = document.getElementById('notificationOkBtn');

    if (!modal || !content || !icon || !titleEl || !messageEl || !okBtn) {
      return;
    }

    titleEl.textContent = title;
    messageEl.textContent = message;

    icon.innerHTML = '';
    icon.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10 ';

    let iconSvg = '';
    let bgColor = '';

    if (type === 'success') {
      bgColor = 'bg-green-100';
      iconSvg = '<svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
      okBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm';
    } else if (type === 'error') {
      bgColor = 'bg-red-100';
      iconSvg = '<svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
      okBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm';
    } else if (type === 'warning') {
      bgColor = 'bg-yellow-100';
      iconSvg = '<svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
      okBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm';
    } else {
      bgColor = 'bg-blue-100';
      iconSvg = '<svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
      okBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm';
    }

    icon.className += bgColor;
    icon.innerHTML = iconSvg;

    modal.classList.remove('hidden');

    const closeNotification = () => {
      modal.classList.add('hidden');
    };

    okBtn.onclick = closeNotification;

    if (autoClose) {
      setTimeout(closeNotification, 5000);
    }
  }

  let confirmationCallback = null;

  function showConfirmation(title, message, onConfirm) {
    const modal = document.getElementById('confirmationModal');
    const titleEl = document.getElementById('confirmationTitle');
    const messageEl = document.getElementById('confirmationMessage');
    const confirmBtn = document.getElementById('confirmationConfirmBtn');
    const cancelBtn = document.getElementById('confirmationCancelBtn');

    if (!modal || !titleEl || !messageEl || !confirmBtn || !cancelBtn) {
      if (onConfirm) onConfirm();
      return;
    }

    titleEl.textContent = title;
    messageEl.textContent = message;
    confirmationCallback = onConfirm;

    modal.classList.remove('hidden');

    const closeModal = () => {
      modal.classList.add('hidden');
      confirmationCallback = null;
    };

    confirmBtn.onclick = () => {
      if (confirmationCallback) {
        confirmationCallback();
      }
      closeModal();
    };

    cancelBtn.onclick = closeModal;
  }

  function closeConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
      modal.classList.add('hidden');
      confirmationCallback = null;
    }
  }

  window.showNotification = showNotification;
  window.showConfirmation = showConfirmation;
  window.closeConfirmationModal = closeConfirmationModal;
</script>

<script>
  async function loadConfig() {
    try {
      const response = await fetch('/hub/deployment/config', {
        method: 'GET',
        headers: {
          'X-CSRF-Token': window.csrfToken || ''
        }
      });
      const data = await response.json();
      if (data.success && data.config) {
        const config = data.config;
        if (config.server) {
          if (config.server.name) document.getElementById('serverName').value = config.server.name;
          if (config.server.region) document.getElementById('serverRegion').value = config.server.region;
          if (config.server.size) document.getElementById('serverSize').value = config.server.size;
          if (config.server.image) document.getElementById('serverImage').value = config.server.image;
        }
        if (config.provision) {
          if (config.provision.php_version) document.getElementById('phpVersion').value = config.provision.php_version;
          if (config.provision.database_type) document.getElementById('databaseType').value = config.provision.database_type;
          if (config.provision.database_version) document.getElementById('databaseVersion').value = config.provision.database_version;
          if (config.provision.database_name) document.getElementById('databaseName').value = config.provision.database_name;
        }
        if (config.deployment) {
          if (config.deployment.domain) document.getElementById('deploymentDomain').value = config.deployment.domain;
          if (config.deployment.ssl_email) document.getElementById('sslEmail').value = config.deployment.ssl_email;
        }
      }
    } catch (error) {
      console.error('Error loading config:', error);
    }
  }

  function closeConfigModal() {
    const modal = document.getElementById('configModal');
    if (modal) modal.classList.add('hidden');
  }

  document.getElementById('configForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const config = { server: {}, provision: {}, deployment: {} };
    for (const [key, value] of formData.entries()) {
      const parts = key.split('[');
      if (parts.length === 2) {
        const section = parts[0];
        const field = parts[1].replace(']', '');
        if (section === 'server' || section === 'provision' || section === 'deployment') {
          config[section][field] = value;
        }
      }
    }
    try {
      const response = await fetch('/hub/deployment/config', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        },
        body: JSON.stringify({ config })
      });
      const data = await response.json();
      if (data.success) {
        showNotification('success', 'Configuration Saved', 'Configuration saved successfully');
        closeConfigModal();
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification('error', 'Save Failed', 'Failed to save configuration: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      showNotification('error', 'Error', 'Error saving configuration: ' + error.message);
    }
  });

  document.getElementById('configModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeConfigModal();
  });

  const editConfigBtn = document.getElementById('editConfigBtn');
  if (editConfigBtn) {
    editConfigBtn.addEventListener('click', function () {
      const modal = document.getElementById('configModal');
      if (modal) {
        modal.classList.remove('hidden');
        loadConfig();
      }
    });
  }

  let currentLogsDeploymentId = null;

  function closeLogsModal() {
    const modal = document.getElementById('logsModal');
    if (modal) {
      modal.classList.add('hidden');
      currentLogsDeploymentId = null;
    }
  }

  function refreshLogs() {
    if (currentLogsDeploymentId) {
      loadLogs(currentLogsDeploymentId);
    }
  }

  async function loadLogs(deploymentId) {
    currentLogsDeploymentId = deploymentId;
    const logsContent = document.getElementById('logsContent');
    if (!logsContent) return;
    logsContent.textContent = 'Loading logs...';
    try {
      const response = await fetch(`/hub/deployment/logs/${deploymentId}`, {
        method: 'GET',
        headers: { 'X-CSRF-Token': window.csrfToken || '' }
      });
      const data = await response.json();
      if (data.success) {
        logsContent.textContent = data.logs || 'No logs available';
        logsContent.scrollTop = logsContent.scrollHeight;
      } else {
        logsContent.textContent = 'Failed to load logs: ' + (data.message || 'Unknown error');
      }
    } catch (error) {
      logsContent.textContent = 'Error loading logs: ' + error.message;
    }
  }

  document.getElementById('logsModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeLogsModal();
  });

  window.viewLogs = function (deploymentId) {
    const modal = document.getElementById('logsModal');
    if (modal) {
      modal.classList.remove('hidden');
      loadLogs(deploymentId);
    }
  };

  async function loadSecrets() {
    try {
      const response = await fetch('/hub/deployment/secrets', {
        method: 'GET',
        headers: { 'X-CSRF-Token': window.csrfToken || '' }
      });
      const data = await response.json();
      if (data.success && data.secrets) {
        const digitaloceanInput = document.getElementById('digitaloceanToken');
        const cloudflareInput = document.getElementById('cloudflareToken');
        if (digitaloceanInput && data.secrets.digitalocean_api_token) {
          digitaloceanInput.placeholder = 'Current value: ' + data.secrets.digitalocean_api_token;
        }
        if (cloudflareInput && data.secrets.cloudflare_api_token) {
          cloudflareInput.placeholder = 'Current value: ' + data.secrets.cloudflare_api_token;
        }
      }
    } catch (error) {
      console.error('Error loading secrets:', error);
    }
  }

  function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + 'Icon');
    if (input && icon) {
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  }

  function closeSecretsModal() {
    const modal = document.getElementById('secretsModal');
    if (modal) modal.classList.add('hidden');
  }

  document.getElementById('secretsForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const secrets = {
      digitalocean_api_token: formData.get('digitalocean_api_token') || '••••••••',
      cloudflare_api_token: formData.get('cloudflare_api_token') || '••••••••',
    };
    try {
      const response = await fetch('/hub/deployment/secrets', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        },
        body: JSON.stringify({ secrets })
      });
      const data = await response.json();
      if (data.success) {
        showNotification('success', 'Secrets Updated', 'Secrets updated successfully');
        closeSecretsModal();
      } else {
        showNotification('error', 'Update Failed', 'Failed to update secrets: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      showNotification('error', 'Error', 'Error updating secrets: ' + error.message);
    }
  });

  document.getElementById('secretsModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeSecretsModal();
  });

  const manageSecretsBtn = document.getElementById('manageSecretsBtn');
  if (manageSecretsBtn) {
    manageSecretsBtn.addEventListener('click', function () {
      const modal = document.getElementById('secretsModal');
      if (modal) {
        modal.classList.remove('hidden');
        loadSecrets();
      }
    });
  }
</script>
