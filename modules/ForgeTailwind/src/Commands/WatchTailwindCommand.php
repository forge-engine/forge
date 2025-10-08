<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'tailwind:watch', description: 'Watch & rebuild Tailwind CSS (binary)')]
class WatchTailwindCommand extends Command
{
    public function execute(array $args): int
    {
        $cfg = require BASE_PATH . '/app/resources/tailwind.config.php';

        $bin = BASE_PATH . '/storage/bin/tailwindcss';
        $in  = escapeshellarg($cfg['input_css']  ?? BASE_PATH . '/app/resources/assets/css/tailwind.css');
        $out = escapeshellarg($cfg['output_css'] ?? BASE_PATH . '/public/assets/css/app.css');

        $this->info('Watching … (Ctrl-C to stop)', 'TailwindWatch');
        passthru(escapeshellarg($bin) . ' -i ' . $in . ' -o ' . $out . ' --minify --watch');
        return 0;
    }
}
