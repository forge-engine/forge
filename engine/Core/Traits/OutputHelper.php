<?php

namespace Forge\Core\Traits;

trait OutputHelper
{
    /**
     * Output an informational message.
     *
     * @param string $message The message to display.
     */
    protected function info(string $message): void
    {
        echo "\033[34m{$message}\033[0m\n";
    }

    /**
     * Output an error message.
     *
     * @param string $message The error message to display.
     */
    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }

    /**
     * Output a line of text.
     *
     * @param string $message The message to display.
     */
    protected function line(string $message): void
    {
        echo "{$message}\n";
    }

    /**
     * Output a comment message.
     *
     * @param string $message The comment message to display.
     */
    protected function comment(string $message): void
    {
        echo "\033[36m// {$message}\033[0m\n";
    }

    protected function warning(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }

    protected function success(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
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

    /**
     * Output an array in a readable format.
     *
     * @param array $array The array to display.
     */
    protected function printArray(array $array): void
    {
        echo "\033[36mArray:\033[0m\n";
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                echo "\033[36m{$key}:\033[0m\n";
                $this->printArray($value);
            } else {
                echo "\033[36m{$key}:\033[0m {$value}\n";
            }
        }
    }
}
