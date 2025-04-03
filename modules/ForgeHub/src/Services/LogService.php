<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use App\Modules\ForgeNexus\Models\LogEntry;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\Config\Config;
use Generator;
use SplFileInfo;
use DirectoryIterator;

#[Service]
#[Provides]
#[Requires(Config::class)]
final class LogService
{
    private const MAX_FILE_SIZE = 10485760; // 10MB

    private const LOG_PATH = BASE_PATH . '/storage/logs/';
    private string $logPath;

    public function __construct(
        private Config $config
    ) {
        $this->logPath = self::LOG_PATH;
    }

    /** @return SplFileInfo[] */
    public function getLogFiles(): array
    {
        $iterator = new DirectoryIterator(self::LOG_PATH);
        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getSize() < self::MAX_FILE_SIZE && !$file->isDot() && strpos($file->getFilename(), '.') !== 0) {
                $files[] = $file->getFileInfo();
            }
        }

        return $files;
    }

    /** @return Generator<LogEntry> */
    public function getLogEntries(
        ?string $filename = null,
        ?string $search = null,
        ?string $date = null
    ): Generator {
        $file = $this->validateFile($filename);

        foreach ($this->readFileLines($file) as $line) {
            try {
                $entry = LogEntry::fromString($line);
                if ($this->matchesFilters($entry, $search, $date)) {
                    yield $entry;
                }
            } catch (\Throwable $e) {
                // Log or handle parsing errors if needed
                continue;
            }
        }
    }

    private function validateFile(?string $filename): SplFileInfo
    {
        if (!$filename || !file_exists("$this->logPath/$filename")) {
            throw new \InvalidArgumentException('Invalid log file');
        }

        return new SplFileInfo("$this->logPath/$filename");
    }

    private function readFileLines(SplFileInfo $file): Generator
    {
        $handle = fopen($file->getRealPath(), 'r');

        while (($line = fgets($handle)) !== false) {
            yield $line;
        }

        fclose($handle);
    }

    private function matchesFilters(LogEntry $entry, ?string $search, ?string $date): bool
    {
        return (!$date || $entry->date->format('Y-m-d') === $date)
            && (!$search || stripos($entry->message, $search) !== false);
    }
}
