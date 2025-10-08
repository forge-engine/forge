<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Commands;

use App\Modules\ForgeTailwind\Services\TailwindPurger;
use App\Modules\ForgeTailwind\Traits\TailwindTrait;
use Forge\CLI\Command;
use Forge\CLI\Traits\CommandOptionTrait;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'tailwind:build', description: 'Compile, purge & minify Tailwind (cached/offline-ready)')]
class ForgeTailwindCommand extends Command
{
    use CommandOptionTrait;
    use TailwindTrait;

    private const CONFIG_PHP = BASE_PATH . '/app/resources/tailwind.config.php';
    private const CONTEXT = 'ForgeTailwind/ForgeTailwindCommand';

    /** @var array<string, mixed> The loaded configuration for the module. */
    private array $cfg;

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @throws \JsonException
     */
    public function execute(array $args): int
    {
        $force = $this->option('force', $args) !== null;
        $this->loadConfig();

        $inputCss = $this->cfg['input_css'];
        $outputCss = $this->cfg['output_path'];
        $customCss = $this->cfg['custom_css'];
        $globs = $this->cfg['content'];

        if (!$this->handleTailwindSource(self::CONTEXT)) {
            $this->error("Failed to prepare Tailwind source CSS.", self::CONTEXT);
            return 1;
        }

        $extra = '';
        if ($customCss && is_file($customCss)) {
            $this->info('Using custom CSS → ' . $customCss, self::CONTEXT);
            $extra = file_get_contents($customCss);
        }

        $purger = new TailwindPurger($globs, [], $this->cfg['tokens'] ?? []);

        if ($force) {
            $this->info('Force mode: cache ignored', self::CONTEXT);
            @unlink(BASE_PATH . '/storage/framework/cache/tailwind.json');
        }

        $this->log('Input CSS → ' . $inputCss, self::CONTEXT);
        $css = $purger->purge($inputCss, self::CONFIG_PHP, $extra);

        @mkdir(dirname($outputCss), 0755, true);
        file_put_contents($outputCss, $css);

        $this->info('Tailwind built → ' . $outputCss, self::CONTEXT);
        $this->info('Size: ' . number_format(strlen($css)) . ' bytes', self::CONTEXT);

        return 0;
    }

    private function loadConfig(): void
    {
        $defaults = [
            'input_css' => BASE_PATH . '/app/resources/assets/css/tailwind.css',
            'output_path' => BASE_PATH . '/public/assets/css/forgetailwind.css',
            'custom_css' => null,
            'content' => [],
            'auto_download' => true,
            'version' => '3.4.1',
            'source_url' => 'https://raw.githubusercontent.com/JTorresConsulta/TailwindCSS-offline/refs/heads/main/all-tailwind-classes-full.css',
            'offline_fallback' => true,
            'verify_integrity' => true,
            'fallback_path' => BASE_PATH . '/app/resources/assets/vendor/tailwind.min.css',
        ];

        $moduleCfg = $this->config->get('forge_tailwind', []);

        $fileCfg = is_file(self::CONFIG_PHP) ? (require self::CONFIG_PHP) : [];

        $this->cfg = array_merge($defaults, $moduleCfg, $fileCfg);

        $this->log("autoDownload = " . ($this->cfg['auto_download'] ? 'true' : 'false'), self::CONTEXT);
    }
}
