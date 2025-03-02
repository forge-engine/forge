<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forge - Welcome to Your PHP Framework</title>
    <style>
        body {
            padding: 0 !important;
        }

        .landing-wrapper {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #F8FAFD;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            width: 100%;
        }

        .landing-container {
            text-align: center;
            width: 100vw;
            padding: 2rem; /* Add some padding around the container */
        }

        .forge-logo {
            font-size: 8rem;
            font-weight: 100;
            color: #1B202D;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            text-decoration: none;
        }

        .forge-welcome-text {
            font-size: 1.5rem; /* Slightly larger for welcome text */
            font-weight: 300;
            color: #374151; /* Slightly darker text for better readability */
            margin-bottom: 1.5rem; /* Add more space below welcome text */
            line-height: 1.6; /* Improve line height for readability */
        }

        .forge-getting-started {
            font-size: 1.2rem;
            font-weight: 400; /* Slightly bolder for section headings */
            color: #1B202D;
            margin-top: 2rem; /* Space above the "Getting Started" section */
            margin-bottom: 1rem;
        }

        .forge-getting-started-text {
            font-size: 1.1rem;
            font-weight: 300;
            color: #6B7280; /* Muted color for guide text */
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .forge-code-command {
            background-color: #EDF2F7; /* Light grey background for code blocks */
            padding: 0.75rem 1rem;
            border-radius: 0.375rem; /* Slightly rounded corners */
            font-family: monospace, monospace; /* Monospace font for code */
            font-size: 1rem;
            color: #1A202C; /* Darker text for code */
            display: inline-block; /* To allow margin and padding */
            margin-bottom: 0.5rem;
            overflow-x: auto; /* Enable horizontal scrolling for long commands */
            max-width: 100%; /* Ensure code blocks don't overflow container */
        }


        .forge-links-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap; /* Allow links to wrap on smaller screens */
        }

        .forge-links-list li {
            margin-bottom: 0.7rem;
            padding: 10px 30px; /* Slightly less horizontal padding */
            font-size: 1.1rem; /* Slightly smaller links */
            font-weight: 300; /* Lighter font weight for links */
        }

        .forge-links-list a {
            text-decoration: none;
            color: #1B202D;
            font-weight: 400; /* Bolder font weight for link text */
            transition: color 0.3s ease, text-decoration 0.3s ease;
        }

        .forge-links-list a:hover {
            text-decoration: underline;
            color: #6B6C70;
        }

        .forge-note {
            font-size: 0.9rem;
            color: #9CA3AF; /* Even more muted for the note */
            margin-top: 2rem;
        }
    </style>
</head>
<body>
<div class="layout-wrapper">
    <div class="landing-wrapper">
        <div class="landing-container">
            <h1><p class="forge-logo">Forge</p></h1>

            <p class="forge-welcome-text">
                Welcome to Forge! You've successfully installed the core of your new PHP framework. <br>
                Get ready to build something amazing, entirely on your terms.
            </p>

            <h2 class="forge-getting-started">Getting Started</h2>

            <p class="forge-getting-started-text">
                This is the basic welcome page served by the <strong>Core Router</strong>, a minimal router included
                within Forge Core. <br>
                It's designed to get you up and running immediately. However, to build more complex web applications,
                you'll need a more feature-rich router module.
            </p>

            <p class="forge-getting-started-text">
                <strong>Essential Modules to Install:</strong> For a typical web application, we recommend starting with
                these modules:
            <ul style="list-style: disc; padding-left: 20px; text-align: left; margin-left: auto; margin-right: auto; width: fit-content;">
                <li><strong>Router:</strong> <a
                        href="https://github.com/forge-engine/modules/tree/main/forge-router"><code>forge-router</code></a>
                    - Provides advanced routing features like route groups, middleware, and more.
                </li>
                <li><strong>View Engine:</strong> <a
                        href="https://github.com/forge-engine/modules/tree/main/forge-view-engine"><code>forge-view-engine</code></a>
                    - Enables you to use view templates for dynamic content generation.
                </li>
            </ul>
            </p>

            <p class="forge-getting-started">Installing Modules via Forge Package Manager</p>

            <p class="forge-getting-started-text">
                Forge comes equipped with a built-in Package Manager CLI tool to simplify module installation. Open your
                terminal in your project directory and use the following commands to install the recommended modules:
            </p>

            <p class="forge-getting-started-text">
                To install the <strong><code>forge-router</code></strong> module, run: <br>
                <code class="forge-code-command">php forge.php install:module forge-router</code>
            </p>

            <p class="forge-getting-started-text">
                To install the <strong><code>forge-view-engine</code></strong> module, run: <br>
                <code class="forge-code-command">php forge.php install:module forge-view-engine</code>
            </p>

            <p class="forge-getting-started-text">
                For a list of all available commands, you can always use the help command: <br>
                <code class="forge-code-command">php forge.php help</code>
            </p>


            <p class="forge-getting-started">Alternative: Manual Module Installation</p>

            <p class="forge-getting-started-text">
                If you prefer manual installation or are working in an environment without CLI access, you can download
                modules directly from the <a href="https://github.com/forge-engine/modules">Forge Modules Repository on
                    GitHub</a>. Follow the instructions in the module's README for manual installation.
            </p>

            <nav>
                <ul class="forge-links-list">
                    <li><a href="https://forge-engine.github.io/">Documentation</a></li>
                    <li><a href="https://github.com/forge-engine/modules">Modules</a></li>
                    <li><a href="https://github.com/forge-engine/forge">Forge</a></li>
                    <li><a href="https://github.com/forge-engine">GitHub</a></li>
                </ul>
            </nav>

            <p class="forge-note">
                Forge - The PHP Framework Where You Are In Control.
            </p>
        </div>
    </div>
</div>
</body>
</html>