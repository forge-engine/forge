<?php

use Forge\Core\Helpers\Flash;

/**
 * @var string $title
 * @var string $message
 */

layout(name: "auth", fromModule: true, moduleName: "ForgeAuth");
?>
<div class="space-y-8">
  <!-- Header -->
  <div class="text-center space-y-2">
    <h1 class="text-3xl font-bold text-gray-900">Welcome back</h1>
    <p class="text-sm text-gray-500">Sign in to your account to continue</p>
  </div>

  <!-- Flash Messages -->
  <?php
  $flashMessages = Flash::flat() ?? [];
  if (!empty($flashMessages)):
    ?>
    <div class="space-y-2">
      <?php foreach ($flashMessages as $msg): ?>
        <?php
        $type = $msg['type'] ?? 'info';
        $typeStyles = [
          'error' => 'bg-red-50 border-red-200 text-red-800',
          'success' => 'bg-green-50 border-green-200 text-green-800',
          'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
          'info' => 'bg-blue-50 border-blue-200 text-blue-800',
        ];
        $style = $typeStyles[$type] ?? $typeStyles['info'];
        ?>
        <div class="rounded-lg border p-4 <?= $style ?>">
          <div class="flex items-center gap-2">
            <?php if ($type === 'error'): ?>
              <i class="fa-solid fa-circle-exclamation"></i>
            <?php elseif ($type === 'success'): ?>
              <i class="fa-solid fa-circle-check"></i>
            <?php elseif ($type === 'warning'): ?>
              <i class="fa-solid fa-triangle-exclamation"></i>
            <?php else: ?>
              <i class="fa-solid fa-circle-info"></i>
            <?php endif; ?>
            <p class="text-sm font-medium"><?= htmlspecialchars($msg['message'] ?? '') ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Login Form -->
  <div class="bg-white rounded-xl border border-gray-200 p-8 shadow-sm">
    <?= form_open(attrs: ["class" => "space-y-6"]) ?>
    <div>
      <?= component(name: 'ForgeHub:input', props: [
        'type' => 'text',
        'name' => 'identifier',
        'id' => 'identifier',
        'label' => 'Identifier',
        'placeholder' => 'Enter your identifier',
        'required' => true,
      ]) ?>
    </div>

    <div>
      <?= component(name: 'ForgeHub:input', props: [
        'type' => 'password',
        'name' => 'password',
        'id' => 'password',
        'label' => 'Password',
        'placeholder' => 'Enter your password',
        'required' => true,
      ]) ?>
    </div>

    <div class="flex items-center justify-between text-sm">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="remember"
          class="w-4 h-4 text-gray-900 border-gray-300 rounded focus:ring-gray-900">
        <span class="text-gray-600">Remember me</span>
      </label>
      <a href="#" class="text-gray-900 font-medium hover:text-gray-700">Forgot password?</a>
    </div>

    <div>
      <?= component(name: 'ForgeHub:button', props: [
        'type' => 'submit',
        'variant' => 'primary',
        'size' => 'lg',
        'class' => 'w-full',
        'children' => 'Sign in',
      ]) ?>
    </div>
    <?= form_close() ?>

    <div class="mt-8 pt-6 border-t border-gray-200">
      <p class="text-center text-sm text-gray-600">
        Don't have an account?
        <a href="/auth/register" class="text-gray-900 font-medium hover:text-gray-700 transition-colors">Sign up</a>
      </p>
    </div>
  </div>

  <!-- Additional Info -->
  <div class="text-center pt-2">
    <p class="text-xs text-gray-500">
      By signing in, you agree to our Terms of Service and Privacy Policy
    </p>
  </div>
</div>
