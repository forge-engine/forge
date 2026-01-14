<?php

$sidebarCollapsed = $sidebarCollapsed ?? false;
$sidebarId = $sidebarId ?? 'admin-sidebar';
$containerClasses = class_merge(['flex', 'h-screen', 'bg-gray-50', 'relative'], $class ?? '');
?>
<div class="<?= $containerClasses ?>" data-sidebar-container>
  <?php if (isset($slots['sidebar'])): ?>
    <div class="fixed inset-0 z-40 md:hidden" id="<?= e($sidebarId) ?>-overlay" data-sidebar-overlay
      style="display: none;">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" data-sidebar-backdrop></div>
    </div>

    <aside id="<?= e($sidebarId) ?>"
      class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 -translate-x-full transition-transform duration-300 ease-in-out md:relative md:translate-x-0 <?= $sidebarCollapsed ? 'md:w-16' : 'md:w-64' ?>"
      data-sidebar>
      <?= $slots['sidebar'] ?>
    </aside>
  <?php endif; ?>

  <div class="flex-1 flex flex-col overflow-hidden w-full md:w-auto">
    <?php if (isset($slots['header'])): ?>
      <header class="bg-white border-b border-gray-200">
        <?= $slots['header'] ?>
      </header>
    <?php endif; ?>

    <main class="flex-1 overflow-y-auto p-6">
      <?php if (isset($slots['breadcrumb'])): ?>
        <div class="mb-4">
          <?= $slots['breadcrumb'] ?>
        </div>
      <?php endif; ?>

      <?= $slots['default'] ?? $content ?? '' ?>
    </main>
  </div>
</div>

<script>
  (function () {
    const sidebarId = '<?= e($sidebarId) ?>';
    const sidebar = document.getElementById(sidebarId);
    const overlay = document.getElementById(sidebarId + '-overlay');
    const backdrop = overlay?.querySelector('[data-sidebar-backdrop]');

    if (!sidebar) return;

    function isMobile() {
      return window.innerWidth < 768;
    }

    function openSidebar() {
      if (!isMobile()) return;
      sidebar.classList.remove('-translate-x-full');
      sidebar.classList.add('translate-x-0');
      if (overlay) {
        overlay.style.display = 'block';
        setTimeout(() => {
          if (backdrop) backdrop.style.opacity = '1';
        }, 10);
      }
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      if (!isMobile()) return;
      sidebar.classList.add('-translate-x-full');
      sidebar.classList.remove('translate-x-0');
      if (overlay) {
        if (backdrop) backdrop.style.opacity = '0';
        setTimeout(() => {
          overlay.style.display = 'none';
        }, 300);
      }
      document.body.style.overflow = '';
    }

    function toggleSidebar() {
      if (!isMobile()) return;
      if (sidebar.classList.contains('-translate-x-full')) {
        openSidebar();
      } else {
        closeSidebar();
      }
    }

    document.addEventListener('click', function (e) {
      const toggleBtn = e.target.closest('[data-sidebar-toggle]');
      if (toggleBtn) {
        e.preventDefault();
        toggleSidebar();
        return;
      }

      const closeBtn = e.target.closest('[data-sidebar-close]');
      if (closeBtn) {
        e.preventDefault();
        closeSidebar();
        return;
      }

      const sidebarLink = e.target.closest('[data-sidebar-link]');
      if (sidebarLink && window.innerWidth < 768) {
        setTimeout(() => {
          closeSidebar();
        }, 150);
        return;
      }

      if (backdrop && e.target === backdrop) {
        closeSidebar();
        return;
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isMobile() && !sidebar.classList.contains('-translate-x-full')) {
        closeSidebar();
      }
    });

    let resizeTimeout;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(function () {
        if (!isMobile()) {
          sidebar.classList.remove('-translate-x-full');
          sidebar.classList.add('translate-x-0');
          if (overlay) overlay.style.display = 'none';
          document.body.style.overflow = '';
        } else {
          if (!sidebar.classList.contains('-translate-x-full')) {
            closeSidebar();
          }
        }
      }, 100);
    });

    window.adminSidebar = {
      open: openSidebar,
      close: closeSidebar,
      toggle: toggleSidebar
    };
  })();
</script>
