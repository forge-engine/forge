<?php
/**
 * @var string $theme
 * @var string $styles
 * @var string $environment
 * @var Throwable $exception
 * @var \Forge\Http\Request $request
 * @var array $stack_trace
 */
?>
<!DOCTYPE html>
<html data-theme="<?= htmlspecialchars($theme, ENT_QUOTES) ?>" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $exception->getCode() ?>: <?= htmlspecialchars($exception->getMessage()) ?></title>
    <link rel="stylesheet" href="/assets/css/error.css">
</head>
<body>
<div class="error-container">
    <header class="error-header">
        <div class="left">
            <div class="exception-tag">
                <a href="#" class="exception-link">ErrorException</a>
            </div>
            <h1 class="error-title">
                <span class="error-code"><?= htmlspecialchars($exception->getMessage()) ?></span>
            </h1>
        </div>
        <div class="right">
            <?php if ($environment !== 'production'): ?>
                <div>
                    <div class="environment-badge">
                        <?= strtoupper(htmlspecialchars($environment)) ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="php-version">PHP <?= phpversion() ?></div>
        </div>
    </header>
    <div class="layout">
        <aside class="file-list">
            <nav class="file-nav">
                <?php foreach ($stack_trace as $index => $trace): ?>
                    <button class="file-button <?= $index === 0 ? 'active' : '' ?>" data-target="trace-<?= $index ?>">
                        <?= htmlspecialchars($trace['function']) ?>
                        <?= htmlspecialchars($trace['file'] !== null ? $trace['file'] : '') ?>:<?= $trace['line'] ?>
                    </button>
                <?php endforeach; ?>
            </nav>
        </aside>
        <div class="main-content">
            <div class="stack-trace-container">
                <?php foreach ($stack_trace as $index => $trace): ?>
                    <div id="trace-<?= $index ?>" class="stack-trace-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="trace-header">
                            #<?= $index + 1 ?> <?= htmlspecialchars($trace['function']) ?>
                        </div>
                        <?php if (isset($trace['file'])): ?>
                            <div class="trace-file">
                                <?= htmlspecialchars($trace['file']) ?>:<?= $trace['line'] ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($trace['code_snippet'])): ?>
                            <pre class="code-snippet"><?php foreach ($trace['code_snippet'] as $line => $code): ?>
                                    <div class="code-line <?= $line === $trace['line'] ? 'highlighted-line' : '' ?>">
                                        <span class="line-number"><?= $line ?></span>
                                        <span class="line-content"><?= htmlspecialchars($code) ?></span>
                                    </div>
                                <?php endforeach; ?></pre>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="request-details">
                <nav class="tab-nav">
                    <button class="tab-button active" data-target="headers">Headers</button>
                    <button class="tab-button" data-target="parameters">Parameters</button>
                    <button class="tab-button" data-target="session">Session</button>
                </nav>

                <div id="headers" class="tab-content active">
                    <pre class="code-snippet"><?= htmlspecialchars(print_r($request->getHeaders(), true)) ?></pre>
                </div>
                <div id="parameters" class="tab-content">
                    <pre class="code-snippet"><?= htmlspecialchars(print_r($request->all(), true)) ?></pre>
                </div>
                <div id="session" class="tab-content">
                    <?php if (session_status() === PHP_SESSION_ACTIVE): ?>
                        <pre class="code-snippet"><?= htmlspecialchars(print_r($_SESSION, true)) ?></pre>
                    <?php else: ?>
                        <div class="code-snippet">No active session</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.file-button').forEach(button => {
            button.addEventListener('click', function () {
                const targetTrace = document.getElementById(button.dataset.target);
                document.querySelectorAll('.file-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.stack-trace-item').forEach(item => item.classList.remove('active'));
                button.classList.add('active');
                targetTrace.classList.add('active');
            });
        });

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function () {
                const targetTab = document.getElementById(button.dataset.target);
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                button.classList.add('active');
                targetTab.classList.add('active');
            });
        });
    });
</script>
</body>
</html>