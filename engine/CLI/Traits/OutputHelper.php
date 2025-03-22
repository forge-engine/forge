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

    protected function debug(string $message): void
    {
        echo "\033[35m{$message}\033[0m\n";
    }

    protected function log(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
    }

    protected function prompt(string $message): void
    {
        echo "\033[36m{$message}\033[0m ";
    }

    protected function array(array $data, ?string $title = null): void
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

    protected function table(array $headers, array $rows): void
    {
        if (empty($headers) || empty($rows)) {
            return;
        }

        // Calculate column widths
        $columnsWidth = array_map('strlen', $headers);
        foreach ($rows as $row) {
            if (is_array($row)) {
                foreach ($row as $key => $value) {
                    $columnIndex = array_search($key, $headers, true);
                    if ($columnIndex !== false) {
                        $columnsWidth[$columnIndex] = max($columnsWidth[$columnIndex], strlen((string) $value));
                    }
                }
            } elseif (is_object($row)) {
                foreach ($headers as $index => $header) {
                    if (isset($row->$header)) {
                        $columnsWidth[$index] = max($columnsWidth[$index], strlen((string) $row->$header));
                    }
                }
            }
        }

        // Output header
        $this->line('| ' . implode(' | ', array_map(function ($header, $width) {
            return str_pad($header, $width);
        }, $headers, $columnsWidth)) . ' |');

        // Output separator
        $separator = '+';
        foreach ($columnsWidth as $width) {
            $separator .= str_repeat('-', $width + 2) . '+';
        }
        $this->line($separator);

        // Output rows
        foreach ($rows as $row) {
            $rowOutput = '| ';
            if (is_array($row)) {
                foreach ($headers as $header) {
                    $value = $row[$header] ?? '';
                    $columnIndex = array_search($header, $headers, true);
                    $rowOutput .= str_pad((string) $value, $columnsWidth[$columnIndex]) . ' | ';
                }
            } elseif (is_object($row)) {
                foreach ($headers as $header) {
                    $value = $row->$header ?? '';
                    $columnIndex = array_search($header, $headers, true);
                    $rowOutput .= str_pad((string) $value, $columnsWidth[$columnIndex]) . ' | ';
                }
            }
            $this->line(rtrim($rowOutput, '| '));
        }
    }
}
