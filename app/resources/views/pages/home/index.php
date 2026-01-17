<?php

use Forge\Core\Helpers\Flash;

/**
 * @var string $title
 * @var object $paginator
 */

layout(name: "main");
?>
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-12">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-900 rounded-2xl mb-4">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
        </svg>
      </div>
      <h1 class="text-4xl font-bold text-gray-900 mb-2">Forge Kernel</h1>
      <p class="text-lg text-gray-600">Welcome to your development playground</p>
    </div>

    <!-- Flash Messages -->
    <?php if (Flash::has('success')): ?>
      <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <p class="text-sm font-medium text-green-800"><?= htmlspecialchars(Flash::get('success')) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <?php if (Flash::has('error')): ?>
      <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
          </svg>
          <p class="text-sm font-medium text-red-800"><?= htmlspecialchars(Flash::get('error')) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Users List Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Users</h2>
            <p class="text-sm text-gray-500 mt-1">List of registered users</p>
          </div>
          <div class="p-6">
            <?php if (count($paginator->items()) > 0): ?>
              <div class="space-y-3">
                <?php foreach ($paginator->items() as $user): ?>
                  <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center space-x-3">
                      <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-gray-700">
                          <?= strtoupper(substr($user['identifier'] ?? $user['email'] ?? 'U', 0, 2)) ?>
                        </span>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['identifier'] ?? 'N/A') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
                      </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($user['status'] ?? 'inactive') === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                      <?= htmlspecialchars(ucfirst($user['status'] ?? 'inactive')) ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>

              <!-- Pagination -->
              <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-between mb-4">
                  <p class="text-sm text-gray-600"><?= pagination_info($paginator) ?></p>
                </div>
                <div class="flex items-center justify-center space-x-2">
                  <?= pagination($paginator) ?>
                </div>
              </div>
            <?php else: ?>
              <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="mt-4 text-sm text-gray-500">No users found</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- File Upload Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">File Upload</h2>
            <p class="text-sm text-gray-500 mt-1">Test file upload functionality</p>
          </div>
          <div class="p-6">
            <?= form_open('/__upload', 'POST', ['enctype' => 'multipart/form-data', 'class' => 'space-y-4']) ?>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Select File</label>
              <?= upload_input('file', 'avatars', ['csrf' => false, 'class' => 'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800']) ?>
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
              Upload File
            </button>
            <?= form_close() ?>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Create User Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Create User</h2>
            <p class="text-sm text-gray-500 mt-1">Register a new user account</p>
          </div>
          <div class="p-6">
            <?= form_open("/", "POST", ["class" => "space-y-4"]) ?>
            <div>
              <label for="identifier" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
              <input id="identifier" name="identifier" type="text" required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors"
                placeholder="Enter username">
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input id="email" name="email" type="email" autocomplete="email" required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors"
                placeholder="user@example.com">
            </div>
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
              <input id="password" name="password" type="password" required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors"
                placeholder="Enter password">
            </div>
            <button type="submit"
              class="w-full px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
              Create User
            </button>
            <?= form_close() ?>
          </div>
        </div>

        <!-- Quick Links Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Quick Links</h2>
          </div>
          <div class="p-6">
            <div class="space-y-2">
              <a href="/hub" class="block px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v6a1 1 0 001 1h3m10-11l2 2m-2-2v6a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-3a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 001 1m-6 0h6"></path>
                  </svg>
                  ForgeHub Dashboard
                </span>
              </a>
              <a href="/auth/login" class="block px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                  </svg>
                  Login
                </span>
              </a>
              <a href="/auth/register" class="block px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                  </svg>
                  Register
                </span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
