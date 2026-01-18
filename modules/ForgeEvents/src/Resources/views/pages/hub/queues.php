<?php

layout(name: "hub", fromModule: true, moduleName: "ForgeHub");
?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Queue Jobs</h1>
    <p class="text-sm text-gray-500 mt-1">Manage and monitor queue jobs</p>
  </div>

  <!-- Stats Cards - Separate island -->
  <div <?= fw_id('queue-stats') ?> class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Statistics</h2>
        <p class="text-sm text-gray-500">Queue job statistics</p>
      </div>
      <button fw:click="refresh"
        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
          </path>
        </svg>
        Refresh
      </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" fw:target>
      <!-- Total Jobs Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Total Jobs</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars((string) ($stats['total'] ?? 0)) ?>
            </p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
              </path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Pending Jobs Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars((string) ($stats['pending'] ?? 0)) ?>
            </p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Processing Jobs Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Processing</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              <?= htmlspecialchars((string) ($stats['processing'] ?? 0)) ?>
            </p>
          </div>
          <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
              </path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Failed Jobs Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Failed</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars((string) ($stats['failed'] ?? 0)) ?>
            </p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Data Table - Separate island -->
  <div <?= fw_id('queue-table') ?> fw:target>
    <?= component(name: 'ForgeHub:data-table', props: [
      'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'queue', 'label' => 'Queue', 'sortable' => true],
        ['key' => 'status', 'label' => 'Status', 'render' => 'badge'],
        ['key' => 'priority', 'label' => 'Priority', 'render' => 'priority'],
        ['key' => 'event_class', 'label' => 'Event Class', 'sortable' => true],
        ['key' => 'attempts', 'label' => 'Attempts', 'sortable' => true],
        ['key' => 'created_at', 'label' => 'Created', 'render' => 'date'],
      ],
      'rows' => $jobs ?? [],
      'paginator' => $paginator,
      'forgewire' => true,
      'expandable' => false,
      'bulkActions' => true,
      'filters' => ['search', 'status', 'queue'],
      'actions' => [
        'view' => ['action' => 'viewJob', 'param' => 'jobId', 'label' => 'View', 'variant' => 'secondary'],
        'retry' => ['action' => 'retryJob', 'param' => 'jobId', 'label' => 'Retry', 'variant' => 'primary'],
        'trigger' => ['action' => 'triggerJob', 'param' => 'jobId', 'label' => 'Trigger', 'variant' => 'primary'],
        'delete' => ['action' => 'deleteJob', 'param' => 'jobId', 'label' => 'Delete', 'variant' => 'danger'],
      ],
      'bulkRetry' => ['action' => 'bulkRetry'],
      'bulkDelete' => ['action' => 'bulkDelete'],
      'queues' => $queues ?? [],
      'selectedRows' => $selectedJobs ?? [],
      'sortColumn' => $sortColumn ?? 'created_at',
      'sortDirection' => $sortDirection ?? 'desc',
      'search' => $search ?? '',
      'statusFilter' => $statusFilter ?? '',
      'queueFilter' => $queueFilter ?? '',
    ]) ?>
    <?php if (!empty($jobDetails) && ($showJobModal ?? false)): ?>
      <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
          <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
              <h3 class="text-xl font-semibold text-gray-900">
                Job #<?= htmlspecialchars((string) ($jobDetails['id'] ?? '')) ?> Details
              </h3>
              <button fw:click="closeJobModal"
                class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1"
                aria-label="Close modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            <div class="px-6 py-6 space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-gray-500">Queue</p>
                  <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['queue'] ?? 'default')) ?></p>
                </div>
                <div>
                  <p class="text-gray-500">Status</p>
                  <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['status'] ?? '')) ?></p>
                </div>
                <div>
                  <p class="text-gray-500">Priority</p>
                  <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['priority'] ?? 0)) ?></p>
                </div>
                <div>
                  <p class="text-gray-500">Attempts</p>
                  <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['attempts'] ?? 0)) ?></p>
                </div>
                <div>
                  <p class="text-gray-500">Event Class</p>
                  <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['event_class'] ?? '')) ?></p>
                </div>
                <div>
                  <p class="text-gray-500">Created At</p>
                  <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['created_at'] ?? '')) ?></p>
                </div>
              </div>

              <?php if (isset($jobDetails['details']['payload'])): ?>
                <div>
                  <h4 class="text-sm font-semibold text-gray-700 mb-2">Payload</h4>
                  <div
                    class="bg-gray-900 text-gray-100 rounded-lg p-4 max-h-[50vh] overflow-y-auto font-mono text-xs border border-gray-700">
                    <pre class="whitespace-pre-wrap break-words"><?= htmlspecialchars(json_encode($jobDetails['details']['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (isset($jobDetails['details']['metadata'])): ?>
                <div>
                  <h4 class="text-sm font-semibold text-gray-700 mb-2">Metadata</h4>
                  <dl class="grid grid-cols-2 gap-2 text-sm">
                    <?php foreach ($jobDetails['details']['metadata'] as $key => $value): ?>
                      <dt class="font-medium text-gray-500"><?= htmlspecialchars(ucfirst((string) $key)) ?>:</dt>
                      <dd class="text-gray-900"><?= htmlspecialchars((string) $value) ?></dd>
                    <?php endforeach; ?>
                  </dl>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
