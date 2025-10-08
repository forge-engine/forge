<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'tailwind:build', description: 'Build Tailwind CSS (binary)')]
class BuildTailwindCommand extends Command
{
    public function execute(array $args): int
    {
        $cfg = require BASE_PATH . '/app/resources/tailwind.config.php';

        $bin  = BASE_PATH . '/storage/bin/tailwindcss';
        $in   = escapeshellarg($cfg['input_css']  ?? BASE_PATH . '/app/resources/assets/css/tailwind.css');
        $out  = escapeshellarg($cfg['output_css'] ?? BASE_PATH . '/public/assets/css/app.css');

        $cmd = escapeshellarg($bin) . ' -i ' . $in . ' -o ' . $out . ' --minify 2>&1';
        exec($cmd, $out, $ret);

        if ($ret !== 0) {
            $this->error("TailwindBuild, {$out}");
            return 1;
        }

        $this->info('Tailwind built', 'TailwindBuild');
        return 0;
    }
}
