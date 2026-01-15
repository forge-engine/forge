<?php

use App\Modules\ForgeAuth\Models\User;

/**
 * @var string $title
 * @var string $message
 * @var User $user
 */

layout(name: "main");
?>
<section class="container">
  <h2 class="text-2xl">Forge Kernel Welcome Page</h2>
  <!-- <ul>
        <li class="text-6xl">PHP Version <?= PHP_VERSION ?></li>
        <li>Database Driver: <?= env("DB_DRIVER") ?></li>
    </ul> -->
  <!-- <h3>User information</h3>
    <pre>
        <?php print_r($user); ?>
    </pre> -->

  <section>
    <?= form_open("/users", "POST", ["class" => "form"]) ?>
    <div class="grid grid--2 mb-sm">
      <input class="form--input" type="text" name="identifier" placeholder="Username" required>
      <input class="form--input" type="password" name="password" placeholder="Password" required>
    </div>
    <input class="form--input mb-sm" type="email" name="email" autocomplete="email" placeholder="Email" required>
    <button class="button" type="submit">Create User</button>
    <?= form_close() ?>
    <?php if ($user): ?>
      <?= form_open("/{$user->id}", "PATCH", ["class" => "form"]) ?>
      <input class="form--input" type="text" name="identifier" placeholder="Username" value="<?= $user->identifier ?>"
        required>
      <input type="email" name="email" placeholder="Email" value="<?= $user->email ?>" required>
      <button class="button" type="submit">Update User</button>
      <?= form_close() ?>
    <?php endif; ?>
  </section>
</section>
