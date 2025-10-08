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

        $bin_path  = BASE_PATH . '/storage/bin';
        $bin  = $bin_path . '/tailwindcss'; 

        if (!file_exists($bin)) {
            $this->info('Tailwind CSS binary not found. Downloading and setting up...', 'TailwindSetup');

            if (!is_dir($bin_path)) {
                mkdir($bin_path, 0755, true);
            }

            $download_url = 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-macos-arm64';
            $temp_bin_name = 'tailwindcss-macos-arm64';
            $temp_bin_path = $bin_path . '/' . $temp_bin_name;

            $download_cmd = "curl -sLO {$download_url} -o " . escapeshellarg($temp_bin_path) . " 2>&1";
            exec($download_cmd, $out, $ret);

            if ($ret !== 0) {
                $this->error("Failed to download Tailwind CSS binary: " . implode("\n", $out), 'TailwindSetup');
                return 1;
            }

            $chmod_cmd = "chmod +x " . escapeshellarg($temp_bin_path) . " 2>&1";
            exec($chmod_cmd, $out, $ret);

            if ($ret !== 0) {
                $this->error("Failed to set executable permission for Tailwind CSS binary: " . implode("\n", $out), 'TailwindSetup');
                unlink($temp_bin_path);
                return 1;
            }

            $mv_cmd = "mv " . escapeshellarg($temp_bin_path) . " " . escapeshellarg($bin) . " 2>&1";
            exec($mv_cmd, $out, $ret);

            if ($ret !== 0) {
                $this->error("Failed to rename/move Tailwind CSS binary: " . implode("\n", $out), 'TailwindSetup');
                unlink($temp_bin_path);
                return 1;
            }

            $this->info('Tailwind CSS binary setup complete.', 'TailwindSetup');
        }


        $in   = escapeshellarg($cfg['input_css']  ?? BASE_PATH . '/app/resources/assets/css/tailwind.css');
        $out  = escapeshellarg($cfg['output_css'] ?? BASE_PATH . '/public/assets/css/app.css');

        $cmd = escapeshellarg($bin) . ' -i ' . $in . ' -o ' . $out . ' --minify 2>&1';
        exec($cmd, $out, $ret);

        if ($ret !== 0) {
            $this->error("TailwindBuild, " . implode("\n", $out));
            return 1;
        }

        $this->info('Tailwind built', 'TailwindBuild');
        return 0;
    }
}