<?php
layout(name: "main", fromModule: true);
?>
<div class="layout-wrapper">
    <div class="landing-wrapper">
        <div class="landing-container">
            <h1>
                <p class="forge-logo">Forge</p>
            </h1>

            <p class="forge-welcome-text">
                You’ve successfully installed the core of your PHP application kernel. <br />
                Forge provides a small, dependency-free foundation — everything else is up to you.
            </p>

            <p class="forge-welcome-text">
                You can build your application in different ways:<br />
                • Create your entire app inside the /app directory.<br />
                • Build features as self-contained modules.<br />
                • Or structure the whole application itself as a set of modules.<br />
            </p>

            <p class="forge-welcome-text">
                Modules can contain routes, controllers, views, services, commands, and configuration.<br />
                They can be enabled, disabled, upgraded, or versioned independently.<br />
                <br /><br />
                There’s no required architecture here.<br />
                Use what you need, ignore what you don’t, and shape the system around your project.
            </p>

            <p class="forge-welcome-text">
                This page itself is provided by the ForgeWelcome module. <br />
                This route is located in: /modules/ForgeWelcome/src/Controllers/WelcomeController.php <br />
                This welcome page is located in: /modules/ForgeWelcome/src/resources/views/pages/index.php <br />
            </p>
            <?= component(name: 'ForgeWelcome:nav-bar', fromModule: true) ?>
            <?= component(name: 'ForgeWelcome:footer', fromModule: true) ?>
        </div>
    </div>
</div>