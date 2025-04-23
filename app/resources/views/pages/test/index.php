<?php
use Forge\Core\View\Component;
use Forge\Core\View\View;

/**
 * @var string $title
 * @var string $userId
 */

View::layout(name: "main", loadFromModule: false);

$alertProps = ["type" => "success", "children" => "Success message"];
?>
<div class="container">
    <h1>Welcome <?= $title ?></h1>
    <?php if ($userId): ?>
    <h3>Welcome user <?= $userId ?></h3>
    <?php endif; ?>

    <?= Component::render("forge-ui:alert", $alertProps, loadFromModule: true) ?>
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