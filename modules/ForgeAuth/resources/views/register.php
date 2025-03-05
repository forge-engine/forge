<?php

use Forge\Http\Session;
use Forge\Http\Request;
use Forge\Core\Helpers\View;

/**
 * @var Session $session
 * @var Request $request
 */
?>
<div class="container">
    <h1>Register to Forge</h1>

    <?php View::component('flash-messages', ['session' => $session]) ?>

    <form method="POST" action="/register">
        <input type="hidden" name="_csrf" value="<?= $request->getCsrfToken() ?>">

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Password Confirmation</label>
            <input type="password" name="password_confirmation" required>
        </div>

        <p>
            Already have an account? <a href="/login">Login here.</a>
        </p>

        <button type="submit">Login</button>
    </form>
</div>