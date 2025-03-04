<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Forge Application</title>
    <link rel="stylesheet" href="/modules/forge-error-handler/css/forge-error-handler.css">
</head>
<body>
<div class="error-container">
    <div class="error-header">
        <div class="left">
            <div class="exception-tag">
                <a href="#" class="exception-link">ErrorException</a>
            </div>
            <h1 class="error-title">
                <span class="error-code"><?= htmlspecialchars($data['error']['message']) ?></span>
            </h1>
        </div>
        <div class="right">
            <?php if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] !== 'production'): ?>
                <div>
                    <div class="environment-badge">
                        <?= strtoupper(htmlspecialchars($_ENV['APP_ENV'] ?? 'DEBUG')) ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="php-version">PHP <?= phpversion() ?></div>
        </div>
    </div>

    <div class="layout">
        <aside class="file-list">
            <nav class="file-nav">
                <?php foreach ($data['error']['trace'] as $index => $trace): ?>
                    <button class="file-button <?= $index === 0 ? 'active' : '' ?>" data-target="trace-<?= $index ?>">
                        <?= htmlspecialchars($trace['function']) ?>
                        <?php if (isset($trace['file'])): ?>
                            <?= htmlspecialchars($trace['file'] !== null ? basename($trace['file']) : '') ?>:<?= $trace['line'] ?? '?' ?>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </nav>
        </aside>
        <div class="main-content">
            <div class="stack-trace-container">
                <?php foreach ($data['error']['trace'] as $index => $trace): ?>
                    <div id="trace-<?= $index ?>" class="stack-trace-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="trace-header">
                            #<?= $index + 1 ?> <?= htmlspecialchars($trace['function']) ?>
                        </div>
                        <?php if (isset($trace['file'])): ?>
                            <div class="trace-file">
                                <?= htmlspecialchars($trace['file']) ?>:<?= $trace['line'] ?? '?' ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($trace['code_snippet'])): ?>
                            <pre class="code-snippet"><?php foreach ($trace['code_snippet'] as $line => $code): ?>
                                    <div
                                        class="code-line <?= $line === ($trace['line'] ?? -1) ? 'highlighted-line' : '' ?>">
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
                    <pre
                        class="code-snippet"><?= htmlspecialchars(print_r($data['request']['headers'] ?? [], true)) ?></pre>
                </div>
                <div id="parameters" class="tab-content">
                    <pre
                        class="code-snippet"><?= htmlspecialchars(print_r($data['request']['parameters'] ?? [], true)) ?></pre>
                </div>
                <div id="session" class="tab-content">
                    <?php if (!empty($data['session'])): ?>
                        <pre class="code-snippet"><?= htmlspecialchars(print_r($data['session'], true)) ?></pre>
                    <?php else: ?>
                        <div class="code-snippet">No active session</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/modules/forge-error-handler/js/forge-error-handler.js" defer></script>
</body>
</html>