<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Commands;

use App\Modules\ForgeTailwind\Services\TailwindPurger;
use App\Modules\ForgeTailwind\Traits\TailwindTrait;
use Forge\CLI\Command;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;
use Forge\CLI\Traits\CommandOptionTrait;
use Forge\Traits\FileHelper;

#[CLICommand(name: 'tailwind:watch', description: 'Watch files & auto-rebuild Tailwind')]
class WatchTailwindCommand extends Command
{
    use CommandOptionTrait;
    use FileHelper;
    use TailwindTrait;

    private const CONFIG_PHP = BASE_PATH . '/app/resources/tailwind.config.php';
    private const STATUS_FILE = BASE_PATH . '/storage/framework/cache/tailwind-watch.json';
    private array $cfg;

    public function __construct(private readonly Config $config)
    {
        $this->loadConfig();
    }

    public function execute(array $args): int
    {
        $this->info('Watching resources...  (Ctrl-C to stop)');
        $lastHash = '';

        $inputCss = $this->cfg['input_css'];
        $outputCss = $this->cfg['output_css'];
        $customCss = $this->cfg['custom_css'] ?? null;
        $globs = $this->cfg['content'];

        $purger = new TailwindPurger($globs);

        while (true) {
            $currentHash = $this->hashWatchedFiles($globs);

            if ($currentHash !== $lastHash) {
                $lastHash = $currentHash;
                $this->info('Change detected, rebuilding Tailwind...');

                if ($customCss && is_file($customCss)) {
                    $this->appendCustomCss($inputCss, $customCss);
                }

                $css = $purger->purge($inputCss, self::CONFIG_PHP);
                @mkdir(dirname($outputCss), 0755, true);
                file_put_contents($outputCss, $css);

                $mtime = filemtime($outputCss) ?: time();
                file_put_contents(self::STATUS_FILE, json_encode(['mtime' => $mtime], JSON_THROW_ON_ERROR));

                $this->info('Tailwind rebuilt → ' . $outputCss . ' (' . number_format(strlen($css)) . ' bytes)');
            }

            usleep(300_000);
        }

        return 0;
    }

    private function loadConfig(): void
    {
        $defaults = [
            'input_css' => BASE_PATH . '/app/resources/assets/css/tailwind.css',
            'output_css' => BASE_PATH . '/public/assets/css/forgetailwind.css',
            'custom_css' => null,
            'content' => [],
        ];

        $fileCfg = is_file(self::CONFIG_PHP) ? (require self::CONFIG_PHP) : [];
        $moduleCfg = $this->config->get('forge_tailwind', []);

        $this->cfg = array_merge($defaults, $moduleCfg, $fileCfg);
    }

    /**
     * Append custom CSS if present
     */
    private function appendCustomCss(string $inputPath, string $customPath): void
    {
        $inputContents = file_get_contents($inputPath);
        $customContents = file_get_contents($customPath);
        file_put_contents($inputPath, $inputContents . "\n\n" . $customContents);
    }
}
