<div class="container">
    <h1>Login to Forge</h1>

    <?php \Forge\Core\Helpers\View::component('flash-messages', ['session' => $session]) ?>

    <form method="POST" action="/login">
        <input type="hidden" name="_csrf" value="<?= $request->getCsrfToken() ?>">

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <p>
            Don't have an account? <a href="/register">Register here.</a>
        </p>
        <button type="submit">Login</button>
    </form>
</div>