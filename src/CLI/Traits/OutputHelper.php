<?php

declare(strict_types=1);

namespace Forge\CLI\Traits;

trait OutputHelper
{
    protected function info(string $message): void
    {
        $this->output("\033[0;34m" . $message . "\033[0m");
    }

    protected function warning(string $message): void
    {
        $this->output("\033[1;33m" . $message . "\033[0m");
    }

    protected function error(string $message): void
    {
        $this->output("\033[0;31m" . $message . "\033[0m");
    }

    protected function comment(string $message): void
    {
        $this->output("\033[0;33m" . $message . "\033[0m");
    }

    protected function array(array $data, string $title = null): void
    {
        if ($title) {
            $this->info($title);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->array($value, $key);
                continue;
            }
            $this->output(sprintf("\033[0;36m%s:\033[0m %s", $key, $value));
        }
    }

    protected function line(string $message = ""): void
    {
        $this->output($message);
    }

    protected function success(string $message): void
    {
        $this->output("\033[1;32m" . $message . "\033[0m");
    }

    private function output(string $message): void
    {
        echo $message . PHP_EOL;
    }
}
