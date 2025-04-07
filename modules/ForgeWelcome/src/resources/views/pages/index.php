<?php

use Forge\Core\View\Component;

/**
 * @var string $title
 */

layout(name: "main", loadFromModule: true);
?>
<div class="layout-wrapper">
    <div class="landing-wrapper">
        <div class="landing-container">
            <h1>
                <p class="forge-logo">Forge</p>
            </h1>

            <p class="forge-welcome-text">
                Welcome to Forge! You've successfully installed the core of your new PHP framework. <br>
                Get ready to build something amazing, entirely on your terms.
            </p>

            <p class="forge-welcome-text">
                This route is located in: /modules/ForgeWelcome/src/Controllers/WelcomeController.php <br />
                This welcome page is located in: /modules/ForgeWelcome/src/resources/views/pages/index.php <br />
            </p>

            <?= Component::render(name: "forge-welcome:navbar", loadFromModule: true, props: []) ?>
            <?= Component::render(name: "forge-welcome:footer", loadFromModule: true, props: []) ?>
        </div>
    </div>
</div>