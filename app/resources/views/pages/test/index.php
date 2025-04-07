<?php
use Forge\Core\View\Component;

/**
 * @var string $title
 */

layout("main");
?>
<div class="container">
    <h1>Welcome <?=$title?></h1>
    <?php if (session()->has('user_id')): ?>
    <h3>Welcome user <?=e(session()->get('user_id'))?></h3>
    <?php endif; ?>

    <?= Component::render("alert", ["type" => "success","children" => "Success message"])?>
    <article class="card">
        <h2 class="card--title">Card Title</h2>
        <div class="card--body">
            <p class="text-xl">Card content</p>
            <button class="button button--secondary">Learn More</button>
        </div>
    </article>

    <!-- Flexbox example -->
    <div class="flex flex--between flex--wrap mt-sm mb-sm">
        <div class="card">...</div>
        <div class="card">...</div>
        <div class="card">...</div>
        <div class="card">...</div>
        <div class="card">...</div>
    </div>

    <!-- Grid example -->
    <div class="grid grid--2">
        <div class="card">...</div>
        <div class="card">...</div>
        <div class="card">...</div>
        <div class="card">...</div>
        <div class="card">...</div>
        <div class="card">...</div>
    </div>

    <!-- Form example -->
    <section>
        <form class="form">
            <div class="form--group">
                <label class="form--label">Email</label>
                <input type="email" class="form--input">
                <div class="form--error form--error--show">Invalid email</div>
            </div>
            <button class="button">Submit</button>
        </form>
    </section>
    <span class="loader"></span>
</div>