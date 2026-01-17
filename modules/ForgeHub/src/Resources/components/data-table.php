<?php

/**
 * @var array $props
 * @var array $columns
 * @var array $rows
 * @var object|null $paginator
 * @var bool $forgewire
 * @var bool $expandable
 * @var bool $bulkActions
 * @var array $actions
 * @var array $filters
 */

$columns = $props['columns'] ?? [];
$rows = $props['rows'] ?? [];
$columnCount = max(1, count($columns));
$paginator = $props['paginator'] ?? null;
$forgewire = $props['forgewire'] ?? false;
$expandable = $props['expandable'] ?? false;
$bulkActions = $props['bulkActions'] ?? false;
$actions = $props['actions'] ?? [];
$filters = $props['filters'] ?? [];
$class = $props['class'] ?? '';
$selectedRows = $props['selectedRows'] ?? [];
$expandedRows = $props['expandedRows'] ?? [];
$sortColumn = $props['sortColumn'] ?? '';
$sortDirection = $props['sortDirection'] ?? 'asc';
$search = $props['search'] ?? '';
$statusFilter = $props['statusFilter'] ?? '';
$queueFilter = $props['queueFilter'] ?? '';
?>
<script>
(function() {
  'use strict';
  if (typeof window.toggleRowDetails === 'undefined') {
    window.toggleRowDetails = function(jobId) {
      const detailsRow = document.getElementById('row-details-' + jobId);
      const toggleIcon = document.getElementById('row-toggle-' + jobId);

      if (!detailsRow || !toggleIcon) return;

      const isHidden = detailsRow.classList.contains('hidden');

      if (isHidden) {
        detailsRow.classList.remove('hidden');
        toggleIcon.classList.add('rotate-90');
        // Smooth height transition
        detailsRow.style.maxHeight = '0';
        detailsRow.style.overflow = 'hidden';
        detailsRow.style.opacity = '0';
        requestAnimationFrame(() => {
          detailsRow.style.transition = 'max-height 0.3s ease-in-out, opacity 0.3s ease-in-out';
          detailsRow.style.maxHeight = detailsRow.scrollHeight + 'px';
          detailsRow.style.opacity = '1';
          setTimeout(() => {
            detailsRow.style.maxHeight = 'none';
            detailsRow.style.transition = '';
          }, 300);
        });
      } else {
        detailsRow.style.maxHeight = detailsRow.scrollHeight + 'px';
        detailsRow.style.transition = 'max-height 0.3s ease-in-out, opacity 0.3s ease-in-out';
        requestAnimationFrame(() => {
          detailsRow.style.maxHeight = '0';
          detailsRow.style.opacity = '0';
          setTimeout(() => {
            detailsRow.classList.add('hidden');
            detailsRow.style.maxHeight = '';
            detailsRow.style.opacity = '';
            detailsRow.style.transition = '';
          }, 300);
        });
        toggleIcon.classList.remove('rotate-90');
      }
    };
  }
})();
</script>
<script>
(function() {
  'use strict';

  // Track row IDs before ForgeWire updates
  let previousRowIds = new Set();
  let isAnimating = false;

  // Function to collect current row IDs
  function collectRowIds() {
    const tbody = document.getElementById('data-table-body');
    if (!tbody) return new Set();

    const rows = tbody.querySelectorAll('tr[data-row-id]');
    const ids = new Set();
    rows.forEach(row => {
      const id = row.getAttribute('data-row-id');
      if (id && !row.classList.contains('row-removing')) ids.add(id);
    });
    return ids;
  }

  // Initialize previous row IDs
  function initTracking() {
    previousRowIds = collectRowIds();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTracking);
  } else {
    initTracking();
  }

  // Function to animate row removal
  function animateRowRemoval(row) {
    if (row.classList.contains('row-removing')) return;

    // Store original height
    const originalHeight = row.offsetHeight;
    row.style.maxHeight = originalHeight + 'px';
    row.style.opacity = '1';
    row.style.transform = 'translateX(0)';

    // Force reflow
    row.offsetHeight;

    // Add removing class and start animation
    row.classList.add('row-removing');
  }

  // Intercept clicks on delete buttons to animate before ForgeWire update
  document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('button[fw\\:click]');
    if (!deleteBtn) return;

    // Check if it's a delete action
    const fwClick = deleteBtn.getAttribute('fw:click');
    if (!fwClick || (!fwClick.toLowerCase().includes('delete'))) return;

    const row = deleteBtn.closest('tr[data-row-id]');
    if (!row) return;

    // Get the row ID
    const rowId = row.getAttribute('data-row-id');
    if (!rowId) return;

    // Animate the row immediately (before ForgeWire updates)
    animateRowRemoval(row);

    // Also animate the details row if it exists
    const detailsRow = document.getElementById('row-details-' + rowId);
    if (detailsRow) {
      animateRowRemoval(detailsRow);
    }

    isAnimating = true;

    // Reset tracking after animation completes (600ms animation + buffer)
    setTimeout(() => {
      isAnimating = false;
      initTracking();
    }, 700);
  }, true); // Use capture phase to intercept before ForgeWire

  // Observe ForgeWire updates to handle bulk deletions
  const observer = new MutationObserver((mutations) => {
    if (isAnimating) return;

    mutations.forEach((mutation) => {
      if (mutation.type === 'childList' && mutation.target.id === 'data-table-body') {
        // Check if rows were removed
        const currentRowIds = collectRowIds();
        const removedIds = [];

        previousRowIds.forEach(id => {
          if (!currentRowIds.has(id)) {
            removedIds.push(id);
          }
        });

        // If rows were removed without animation, they were removed by ForgeWire
        // We can't animate them now, but we can track for next time
        if (removedIds.length > 0) {
          setTimeout(() => {
            initTracking();
          }, 100);
        } else {
          // New rows added - mark them for fade-in
          currentRowIds.forEach(id => {
            if (!previousRowIds.has(id)) {
              const row = document.querySelector(`tr[data-row-id="${id}"]`);
              if (row) {
                row.classList.add('row-adding');
                setTimeout(() => {
                  row.classList.remove('row-adding');
                }, 300);
              }
            }
          });

          setTimeout(() => {
            initTracking();
          }, 100);
        }
      }
    });
  });

  // Start observing
  function startObserving() {
    const tbody = document.getElementById('data-table-body');
    if (tbody) {
      observer.observe(tbody, {
        childList: true,
        subtree: false
      });
    } else {
      setTimeout(startObserving, 100);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startObserving);
  } else {
    startObserving();
  }
})();
</script>
<style>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

/* Row removal animation */
.table-row {
  transition: opacity 0.6s ease-out, transform 0.6s ease-out, max-height 0.6s ease-out, padding 0.6s ease-out, margin 0.6s ease-out;
  overflow: hidden;
}

.table-row.row-removing {
  opacity: 0 !important;
  transform: translateX(-30px) !important;
  max-height: 0 !important;
  margin-top: 0 !important;
  margin-bottom: 0 !important;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
  border-top: none !important;
  border-bottom: none !important;
  pointer-events: none;
}

/* Smooth row appearance */
.table-row.row-adding {
  animation: fade-in 0.3s ease-out;
}
</style>
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden <?= htmlspecialchars($class) ?>">
  <?php if ($forgewire && (!empty($filters) || !empty($search))): ?>
    <div class="p-4 border-b border-gray-200 bg-gray-50">
      <div class="flex flex-col md:flex-row gap-4">
        <?php if (in_array('search', $filters)): ?>
          <div class="flex-1">
            <input type="text"
                   fw:model.debounce="search"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search..."
                   class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
          </div>
        <?php endif; ?>

        <?php if (in_array('status', $filters)): ?>
          <select fw:model="statusFilter"
                  class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            <option value="">All Statuses</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
            <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
            <option value="scheduled" <?= $statusFilter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
          </select>
        <?php endif; ?>

        <?php if (in_array('queue', $filters)): ?>
          <select fw:model="queueFilter"
                  class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            <option value="">All Queues</option>
            <?php foreach ($props['queues'] ?? [] as $queue): ?>
              <option value="<?= htmlspecialchars($queue) ?>" <?= $queueFilter === $queue ? 'selected' : '' ?>>
                <?= htmlspecialchars($queue) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>

        <?php if ($forgewire): ?>
          <button fw:click="clearFilters"
                  class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors">
            Clear Filters
          </button>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($bulkActions && !empty($selectedRows)): ?>
    <div class="p-4 border-b border-gray-200 bg-blue-50 flex items-center justify-between">
      <span class="text-sm text-gray-700">
        <?= count($selectedRows) ?> item(s) selected
      </span>
      <div class="flex gap-2">
        <?php if (isset($actions['bulkRetry'])): ?>
          <button fw:click="bulkRetry"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">
            Retry Selected
          </button>
        <?php endif; ?>
        <?php if (isset($actions['bulkDelete'])): ?>
          <button fw:click="bulkDelete"
                  class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium transition-colors">
            Delete Selected
          </button>
        <?php endif; ?>
        <button fw:click="deselectAll"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium transition-colors">
          Deselect All
        </button>
      </div>
    </div>
  <?php endif; ?>

  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <?php if ($bulkActions): ?>
            <th class="px-4 py-3 text-left">
              <input type="checkbox"
                     fw:click="selectAll"
                     class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </th>
          <?php endif; ?>

          <?php if ($expandable): ?>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12"></th>
          <?php endif; ?>

          <?php foreach ($columns as $column): ?>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <?php if (($column['sortable'] ?? false) && $forgewire): ?>
                <button fw:click="sort"
                        fw:param-column="<?= htmlspecialchars($column['key']) ?>"
                        class="flex items-center gap-1 hover:text-gray-700">
                  <span><?= htmlspecialchars($column['label']) ?></span>
                  <?php if ($sortColumn === $column['key']): ?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <?php if ($sortDirection === 'asc'): ?>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                      <?php else: ?>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                      <?php endif; ?>
                    </svg>
                  <?php endif; ?>
                </button>
              <?php else: ?>
                <?= htmlspecialchars($column['label']) ?>
              <?php endif; ?>
            </th>
          <?php endforeach; ?>

          <?php if (!empty($actions)): ?>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200" fw:target id="data-table-body">
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="<?= max(1, $columnCount + ($bulkActions ? 1 : 0) + ($expandable ? 1 : 0) + (!empty($actions) ? 1 : 0)) ?>"
                class="px-6 py-12 text-center text-sm text-gray-500">
              No data available
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <?php
            $rowId = $row['id'] ?? null;
            $isSelected = $rowId && in_array($rowId, $selectedRows);
            ?>
            <tr data-row-id="<?= htmlspecialchars((string)$rowId) ?>" class="table-row hover:bg-gray-50 <?= $isSelected ? 'bg-blue-50' : '' ?>">
              <?php if ($bulkActions): ?>
                <td class="px-4 py-4">
                  <input type="checkbox"
                         <?= $isSelected ? 'checked' : '' ?>
                         fw:click="toggleJobSelection"
                         fw:param-jobId="<?= htmlspecialchars((string)$rowId) ?>"
                         class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </td>
              <?php endif; ?>

              <?php if ($expandable): ?>
                <td class="px-4 py-4">
                  <button onclick="toggleRowDetails(<?= htmlspecialchars((string)$rowId) ?>)"
                          class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5 transform transition-transform duration-200"
                         id="row-toggle-<?= htmlspecialchars((string)$rowId) ?>"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                  </button>
                </td>
              <?php endif; ?>

              <?php foreach ($columns as $column): ?>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <?php
                  $key = $column['key'];
                  $value = $row[$key] ?? null;

                  if (isset($column['render'])) {
                    switch ($column['render']) {
                      case 'badge':
                        $badgeType = match($value) {
                          'pending' => 'info',
                          'processing' => 'warning',
                          'failed' => 'error',
                          'scheduled' => 'default',
                          default => 'default'
                        };
                        echo component(name: 'ForgeHub:badge', props: ['text' => ucfirst($value ?? ''), 'type' => $badgeType]);
                        break;
                      case 'priority':
                        $priorityColors = [
                          3 => 'bg-red-500',
                          2 => 'bg-yellow-500',
                          1 => 'bg-gray-500',
                        ];
                        $color = $priorityColors[$value] ?? 'bg-gray-500';
                        echo '<span class="inline-block w-2 h-2 rounded-full ' . $color . '"></span>';
                        break;
                      case 'date':
                        if ($value) {
                          $date = new DateTime($value);
                          $now = new DateTime();
                          $diff = $now->diff($date);
                          $relative = '';
                          if ($diff->days > 0) {
                            $relative = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                          } elseif ($diff->h > 0) {
                            $relative = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                          } elseif ($diff->i > 0) {
                            $relative = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                          } else {
                            $relative = 'Just now';
                          }
                          echo '<span title="' . htmlspecialchars($date->format('Y-m-d H:i:s')) . '">' . htmlspecialchars($relative) . '</span>';
                        }
                        break;
                      default:
                        echo htmlspecialchars((string)$value);
                    }
                  } else {
                    echo htmlspecialchars((string)$value);
                  }
                  ?>
                </td>
              <?php endforeach; ?>

              <?php if (!empty($actions)): ?>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2">
                    <?php foreach ($actions as $actionKey => $actionConfig): ?>
                      <?php if ($actionKey === 'bulkRetry' || $actionKey === 'bulkDelete') continue; ?>
                      <?php
                      $actionName = $actionConfig['action'] ?? $actionKey;
                      $paramName = $actionConfig['param'] ?? 'jobId';
                      $paramValue = $row['id'] ?? $rowId;
                      $label = $actionConfig['label'] ?? ucfirst($actionKey);
                      $variant = $actionConfig['variant'] ?? 'secondary';
                      ?>
                      <button fw:click="<?= htmlspecialchars($actionName) ?>"
                              fw:param-<?= htmlspecialchars($paramName) ?>="<?= htmlspecialchars((string)$paramValue) ?>"
                              class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors <?=
                                $variant === 'danger' ? 'bg-red-100 text-red-700 hover:bg-red-200' :
                                ($variant === 'primary' ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' :
                                'bg-gray-100 text-gray-700 hover:bg-gray-200') ?>">
                        <?= htmlspecialchars($label) ?>
                      </button>
                    <?php endforeach; ?>
                  </div>
                </td>
              <?php endif; ?>
            </tr>

            <?php if ($expandable && isset($row['details'])): ?>
              <tr id="row-details-<?= htmlspecialchars((string)$rowId) ?>"
                  class="hidden transition-all duration-300 ease-in-out">
                <td colspan="<?= max(1, $columnCount + ($bulkActions ? 1 : 0) + ($expandable ? 1 : 0) + (!empty($actions) ? 1 : 0)) ?>"
                    class="px-6 py-4 bg-gray-50">
                  <div class="space-y-4 animate-fade-in">
                    <?php if (isset($row['details']['payload'])): ?>
                      <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Payload</h4>
                        <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg text-xs overflow-x-auto"><?= htmlspecialchars(json_encode($row['details']['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($row['details']['error'])): ?>
                      <div>
                        <h4 class="text-sm font-semibold text-red-700 mb-2">Error</h4>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                          <p class="text-sm text-red-800"><?= htmlspecialchars($row['details']['error']) ?></p>
                        </div>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($row['details']['metadata'])): ?>
                      <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Metadata</h4>
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                          <?php foreach ($row['details']['metadata'] as $key => $value): ?>
                            <dt class="font-medium text-gray-500"><?= htmlspecialchars(ucfirst($key)) ?>:</dt>
                            <dd class="text-gray-900"><?= htmlspecialchars((string)$value) ?></dd>
                          <?php endforeach; ?>
                        </dl>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($paginator && $forgewire): ?>
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
      <div class="text-sm text-gray-700">
        <?= pagination_info($paginator) ?>
      </div>
      <div class="flex items-center gap-2">
        <?php if ($paginator->hasPreviousPage()): ?>
          <button fw:click="changePage"
                  fw:param-page="<?= $paginator->currentPage() - 1 ?>"
                  class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            Previous
          </button>
        <?php endif; ?>

        <span class="text-sm text-gray-600">
          Page <?= $paginator->currentPage() ?> of <?= $paginator->lastPage() ?>
        </span>

        <?php if ($paginator->hasMorePages()): ?>
          <button fw:click="changePage"
                  fw:param-page="<?= $paginator->currentPage() + 1 ?>"
                  class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            Next
          </button>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>
