<?php

use Forge\Core\Helpers\ModuleResources;

/**
 * @var string $title
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? "Billing" ?> | ForgeBilling</title>
  <link rel="stylesheet" href="<?= ModuleResources::pathTo(module: 'forge-billing', resource: 'css/billing.css') ?>">
  <link rel="stylesheet" href="/assets/css/app.css" />
  <?= raw(csrf_meta()) ?>
  <script>
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  </script>
</head>
<body>
  <div class="billing-layout">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleBillingSidebar()"></div>

    <aside class="billing-sidebar" id="billingSidebar">
      <div class="billing-sidebar__header">
        <a href="/billing" class="billing-sidebar__brand">
          <span class="billing-sidebar__brand-icon">B</span>
          Billing
        </a>
      </div>
      <nav class="billing-sidebar__nav">
        <?= component(name: 'ForgeBilling:billing-nav') ?>
      </nav>
    </aside>

    <div class="billing-main">
      <header class="billing-main__header">
        <div style="display:flex;align-items:center;gap:0.75rem">
          <button class="billing-main__menu-toggle" onclick="toggleBillingSidebar()" aria-label="Toggle menu">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="M3 5h14a1 1 0 110 2H3a1 1 0 010-2zm0 4h14a1 1 0 110 2H3a1 1 0 110-2zm0 4h14a1 1 0 110 2H3a1 1 0 110-2z"/></svg>
          </button>
          <h1 style="font-size:1.125rem;font-weight:600;color:#111827;"><?= $title ?? "Billing" ?></h1>
        </div>
      </header>

      <main class="billing-main__content">
        <div class="billing-main__inner">
          <?= $content ?>
        </div>
      </main>
    </div>
  </div>

  <script>
    function toggleBillingSidebar() {
      document.getElementById('billingSidebar').classList.toggle('open');
      document.getElementById('sidebarOverlay').classList.toggle('open');
    }
  </script>
</body>
</html>
